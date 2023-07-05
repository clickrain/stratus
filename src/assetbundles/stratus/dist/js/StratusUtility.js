/**
 * Stratus plugin for Craft CMS
 *
 * Stratus JS
 *
 * @author    Joseph Marikle
 * @copyright Copyright (c) 2022 Joseph Marikle
 * @link      clickrain.com
 * @package   Stratus
 * @since     1.0.0
 */

(function ($) {

    if (typeof Craft.Stratus === typeof undefined) {
        Craft.Stratus = {};
    }

    Craft.Stratus.Utility = Garnish.Base.extend({
        $trigger: null,
        $form: null,

        action: null,

        init(settings) {
            this.setSettings(settings, {});

            this.$form = $('#stratus-utility-interface');
            this.$trigger = $('input.submit', this.$form);
            this.$status = $('.utility-status', this.$form);

            this.action = Garnish.getPostData(this.$form).action;

            this.addListener(this.$form, 'submit', 'onSubmit');
        },

        onSubmit(ev) {
            ev.preventDefault();

            if (!this.$trigger.hasClass('disabled')) {
                if (!this.progressBar) {
                  this.progressBar = new Craft.ProgressBar(this.$status);
                } else {
                  this.progressBar.resetProgressBar();
                }

                this.progressBar.$progressBar.removeClass('hidden');

                this.progressBar.$progressBar.velocity('stop').velocity(
                    {
                        opacity: 1,
                    },
                    {
                        complete: () => {
                            var data = this.$form.serial
                            var action = ev.originalEvent.submitter.dataset.action;

                            Craft.sendActionRequest('POST', Craft.getActionUrl(action), {
                                validateStatus: status => status >= 200 && status <= 302,
                            })
                                .then((response) => {
                                    this.updateProgressBar();
                                    setTimeout(this.onComplete.bind(this), 300);
                                })
                                .catch(({response}) => {
                                    this.updateProgressBar();
                                    Craft.cp.displayError(
                                        Craft.t(
                                            'stratus',
                                            'There was a problem executing the request. Please check the Craft logs.'
                                        )
                                    );
                                    this.onComplete(false);
                                });
                        },
                    }
                );

                if (this.$allDone) {
                    this.$allDone.css('opacity', 0);
                }

                this.$trigger.addClass('disabled');
                this.$trigger.trigger('blur');
            }
        },

        updateProgressBar: function () {
            var width = 100;
            this.progressBar.setProgressPercentage(width);
        },

        onComplete: function (showAllDone) {
            if (!this.$allDone) {
                this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(
                    this.$status
                );
                this.$allDone.css('opacity', 0);
            }

            this.progressBar.$progressBar.velocity(
                {opacity: 0},
                {
                    duration: 'fast',
                    complete: () => {
                        if (typeof showAllDone === 'undefined' || showAllDone === true) {
                            this.$allDone.velocity({opacity: 1}, {duration: 'fast'});
                        }

                        this.$trigger.removeClass('disabled');
                        this.$trigger.trigger('focus');

                        // refresh the page
                        Craft.cp.displayNotice(
                            Craft.t('stratus', 'Job queued successfully. Running queue...')
                        );
                        Craft.cp.runQueue();
                    },
                }
            );
        },
    });
})(jQuery);