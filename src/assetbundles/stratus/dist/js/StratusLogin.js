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

    Craft.Stratus.Login = Garnish.Base.extend({

        $container: null,
        $loginButton: null,
        $spinner: null,
        $saveWarning: null,

        webhookUrl: null,
        callbackUrl: null,

        init(settings) {

            this.setSettings(settings, {});

            this.webhookUrl = Craft.getActionUrl('stratus/webhook/handle');
            this.callbackUrl = Craft.getActionUrl('stratus/public/authenticate');

            this.$container = $('#stratus-container');
            this.$loginButton = this.$container.find('#stratus-login');
            this.$spinner = this.$container.find('#stratus-login-spinner');
            this.$saveWarning = this.$container.find("#save-warning");

            this.addListener(this.$loginButton, 'click', this.onLogin);
        },

        onLogin() {
            this.$spinner.removeClass('hidden');
            let windowHandle = this.createOauthWindow(
                this.settings.baseUrl.replace(/[/]+$/, '')
                + '/authenticate?' + (new Date()).getTime()
                + '&callback_url=' + this.callbackUrl
                + '&webhook_url=' + this.webhookUrl
            );

            let params = null;
            let interval = window.setInterval(() => {
                if (windowHandle.closed) {
                    window.clearInterval(interval);
                    this.$spinner.addClass('hidden');
                }

                try {
                    let urlParams = new URLSearchParams(windowHandle.location.search);
                    params = Object.fromEntries(urlParams);
                } catch (error) {
                    return
                }

                if(params.hasOwnProperty('stratus_token')) {
                    this.$container
                        .find('[name=settings\\[apiKey\\]]')
                        .val(params.stratus_token);
                    this.$container
                        .find('[name=settings\\[webhookSecret\\]]')
                        .val(params.stratus_webhook_secret);

                    windowHandle.close();
                    this.$saveWarning.removeClass('hidden');
                }
            }, 250);
        },

        createOauthWindow(url, name = 'Authorization', width = 500, height = 600) {
            // center popup window code obtained from https://stackoverflow.com/a/16861050/854246

            // Fixes dual-screen position                             Most browsers      Firefox
            const dualScreenLeft = window.screenLeft !==  undefined ? window.screenLeft : window.screenX;
            const dualScreenTop = window.screenTop !==  undefined   ? window.screenTop  : window.screenY;

            const screenWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            const windowHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

            const systemZoom = screenWidth / window.screen.availWidth;
            const left = (screenWidth - width) / 2 / systemZoom + dualScreenLeft
            const top = (windowHeight - height) / 2 / systemZoom + dualScreenTop

            const options =`
            scrollbars=yes,
            width=${width / systemZoom},
            height=${height / systemZoom},
            top=${top},
            left=${left}
            `;

            //const options = `width=${width},height=${height},left=${left},top=${top}`;
            return window.open(url, name, options);
        },
    });
})(jQuery);