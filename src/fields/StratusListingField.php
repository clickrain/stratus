<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus\fields;

use clickrain\stratus\assetbundles\stratus\StratusAsset;
use clickrain\stratus\Stratus;
use clickrain\stratus\assetbundles\stratusfieldfield\StratusFieldFieldAsset;
use clickrain\stratus\elements\StratusListingElement;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\BaseRelationField;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * StratusListingField Field
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 */
class StratusListingField extends BaseRelationField
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    // public $someAttribute = 'Some Default';

    /**
     * @var string Template to use for field rendering
     */
    //protected $inputTemplate = 'stratus/_components/fields/StratusField_input';

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('stratus', 'Stratus Listings');
    }

    public static function elementType(): string
    {
        return StratusListingElement::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('stratus', 'Add a Stratus Listing');
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    // public function rules()
    // {
    //     $rules = parent::rules();
    //     $rules = array_merge($rules, [
    //         ['someAttribute', 'string'],
    //         ['someAttribute', 'default', 'value' => 'Some Default'],
    //     ]);
    //     return $rules;
    // }

    /**
     * Returns the column type that this field should get within the content table.
     *
     * This method will only be called if [[hasContentColumn()]] returns true.
     *
     * @return string The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     * to convert the give column type to the physical one. For example, `string` will be converted
     * as `varchar(255)` and `string(100)` becomes `varchar(100)`. `not null` will automatically be
     * appended as well.
     * @see \yii\db\QueryBuilder::getColumnType()
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * Normalizes the field’s value for use.
     *
     * This method is called when the field’s value is first accessed from the element. For example, the first time
     * `entry.myFieldHandle` is called from a template, or right before [[getInputHtml()]] is called. Whatever
     * this method returns is what `entry.myFieldHandle` will likewise return, and what [[getInputHtml()]]’s and
     * [[serializeValue()]]’s $value arguments will be set to.
     *
     * @param mixed                 $value   The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return mixed The prepared field value
     */
    // public function normalizeValue($value, ElementInterface $element = null)
    // {
    //     return $value;
    // }

    /**
     * Returns the component’s settings HTML.
     *
     * An extremely simple implementation would be to directly return some HTML:
     *
     * ```php
     * return '<textarea name="foo">'.$this->getSettings()->foo.'</textarea>';
     * ```
     *
     * For more complex settings, you might prefer to create a template, and render it via
     * [[\craft\web\View::renderTemplate()]]. For example, the following code would render a template loacated at
     * craft/plugins/myplugin/templates/_settings.html, passing the settings to it:
     *
     * ```php
     * return Craft::$app->getView()->renderTemplate('myplugin/_settings', [
     *     'settings' => $this->getSettings()
     * ]);
     * ```
     *
     * If you need to tie any JavaScript code to your settings, it’s important to know that any `name=` and `id=`
     * attributes within the returned HTML will probably get [[\craft\web\View::namespaceInputs() namespaced]],
     * however your JavaScript code will be left untouched.
     *
     * For example, if getSettingsHtml() returns the following HTML:
     *
     * ```html
     * <textarea id="foo" name="foo"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * …then it might actually look like this before getting output to the browser:
     *
     * ```html
     * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id=`
     * attribute was changed from `foo` to `namespace-foo`.
     *
     * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
     * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
     * that simple.
     *
     * Thankfully, [[\craft\web\View]] service provides a couple handy methods that can help you deal
     * with this:
     *
     * - [[\craft\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
     * - [[\craft\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
     * - [[\craft\web\View::formatInputId()]] will format an input name to look more like an ID attribute value.
     *
     * So here’s what a getSettingsHtml() method that includes field-targeting JavaScript code might look like:
     *
     * ```php
     * public function getSettingsHtml()
     * {
     *     // Come up with an ID value for 'foo'
     *     $id = Craft::$app->getView()->formatInputId('foo');
     *
     *     // Figure out what that ID is going to be namespaced into
     *     $namespacedId = Craft::$app->getView()->namespaceInputId($id);
     *
     *     // Render and return the input template
     *     return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
     *         'id'           => $id,
     *         'namespacedId' => $namespacedId,
     *         'settings'     => $this->getSettings()
     *     ]);
     * }
     * ```
     *
     * And the _settings.html template might look like this:
     *
     * ```twig
     * <textarea id="{{ id }}" name="foo">{{ settings.foo }}</textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('{{ namespacedId }}');
     * </script>
     * ```
     *
     * The same principles also apply if you’re including your JavaScript code with
     * [[\craft\web\View::registerJs()]].
     *
     * @return string|null
     */
    // public function getSettingsHtml()
    // {
    //     // Render the settings template
    //     return Craft::$app->getView()->renderTemplate(
    //         'stratus/_components/fields/StratusField_settings',
    //         [
    //             'field' => $this,
    //         ]
    //     );
    // }

    /**
     * Returns the field’s input HTML.
     *
     * An extremely simple implementation would be to directly return some HTML:
     *
     * ```php
     * return '<textarea name="'.$name.'">'.$value.'</textarea>';
     * ```
     *
     * For more complex inputs, you might prefer to create a template, and render it via
     * [[\craft\web\View::renderTemplate()]]. For example, the following code would render a template located at
     * craft/plugins/myplugin/templates/_fieldinput.html, passing the $name and $value variables to it:
     *
     * ```php
     * return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
     *     'name'  => $name,
     *     'value' => $value
     * ]);
     * ```
     *
     * If you need to tie any JavaScript code to your input, it’s important to know that any `name=` and `id=`
     * attributes within the returned HTML will probably get [[\craft\web\View::namespaceInputs() namespaced]],
     * however your JavaScript code will be left untouched.
     *
     * For example, if getInputHtml() returns the following HTML:
     *
     * ```html
     * <textarea id="foo" name="foo"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * …then it might actually look like this before getting output to the browser:
     *
     * ```html
     * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id=`
     * attribute was changed from `foo` to `namespace-foo`.
     *
     * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
     * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
     * that simple.
     *
     * Thankfully, [[\craft\web\View]] provides a couple handy methods that can help you deal with this:
     *
     * - [[\craft\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
     * - [[\craft\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
     * - [[\craft\web\View::formatInputId()]] will format an input name to look more like an ID attribute value.
     *
     * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
     *
     * ```php
     * public function getInputHtml($value, $element)
     * {
     *     // Come up with an ID value based on $name
     *     $id = Craft::$app->getView()->formatInputId($name);
     *
     *     // Figure out what that ID is going to be namespaced into
     *     $namespacedId = Craft::$app->getView()->namespaceInputId($id);
     *
     *     // Render and return the input template
     *     return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
     *         'name'         => $name,
     *         'id'           => $id,
     *         'namespacedId' => $namespacedId,
     *         'value'        => $value
     *     ]);
     * }
     * ```
     *
     * And the _fieldinput.html template might look like this:
     *
     * ```twig
     * <textarea id="{{ id }}" name="{{ name }}">{{ value }}</textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('{{ namespacedId }}');
     * </script>
     * ```
     *
     * The same principles also apply if you’re including your JavaScript code with
     * [[\craft\web\View::registerJs()]].
     *
     * @param mixed                 $value           The field’s value. This will either be the [[normalizeValue() normalized value]],
     *                                               raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element         The element the field is associated with, if there is one
     *
     * @return string The input HTML.
     */
    public function getInputHtml(mixed $value, ?\craft\base\ElementInterface $element = null): string
    {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(StratusAsset::class);

        return parent::getInputHtml($value, $element);
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'building';
    }
}
