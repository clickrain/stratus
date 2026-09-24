<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use craft\web\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Twig\Source;

/**
 * Twig is a blind spot for static analysis: PHPStan never reads these files, so
 * a Craft release that drops a filter, function or tag the templates use only
 * shows up when someone opens the page in the control panel.
 *
 * Compiling each template against Craft's real Twig environment catches exactly
 * that, without needing to render anything.
 */
class TemplateTest extends Unit
{
    protected \IntegrationTester $tester;

    public function testEveryTemplateCompilesAgainstCraftsTwig(): void
    {
        $view = Craft::$app->getView();
        $originalMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $twig = $view->getTwig();
        $failures = [];

        try {
            foreach ($this->templates() as $path => $source) {
                try {
                    $twig->parse($twig->tokenize(new Source($source, $path)));
                } catch (\Twig\Error\Error $e) {
                    $failures[] = sprintf('%s line %d: %s', $path, $e->getTemplateLine(), $e->getRawMessage());
                }
            }
        } finally {
            $view->setTemplateMode($originalMode);
        }

        $this->assertSame([], $failures, "Templates no longer compile:\n  " . implode("\n  ", $failures));
    }

    public function testThereAreTemplatesToCheck(): void
    {
        $this->assertNotEmpty(
            $this->templates(),
            'No templates were found, so the compile test is asserting nothing.',
        );
    }

    /**
     * @return array<string, string> path relative to src/templates => contents
     */
    private function templates(): array
    {
        $root = dirname(__DIR__, 2) . '/src/templates';
        $templates = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file->getExtension() !== 'twig') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1);
            $templates[$relative] = file_get_contents($file->getPathname());
        }

        ksort($templates);

        return $templates;
    }
}
