import store from '../Store.js'
import componentStore from '../Store.js'
import { getCsrfToken, contentIsFromDump, splitDumpFromContent } from '../util/index.js'
import { showHtmlModal } from '../modal.js'

export default class Connection {
    constructor() {
        this.headers = {}
    }

    onMessage(message, payload) {
        message.component.receiveMessage(message, payload)
    }

    onError(message, status, response) {
        message.component.messageSendFailed()

        return componentStore.onErrorCallback(status, response)
    }

    showExpiredMessage(response, message) {
        if (store.sessionHasExpiredCallback) {
            store.sessionHasExpiredCallback(response, message)
        } else {
            confirm(
                'This page has expired.\nWould you like to refresh the page?'
            ) && window.location.reload()
        }
    }

    async sendMessage(message) {
        const payload   = message.payload();
        const csrfToken = getCsrfToken();
        const url = window.raxm_app_url;
    
        try {
            const headers = buildHeaders(csrfToken, this.headers);
            const response = await fetch(`${url}/${payload.fingerprint.name}`, {
                method: 'POST',
                body: JSON.stringify(payload),
                credentials: 'same-origin',
                headers,
            });
    
            /**
             * Sometimes a redirect happens on the backend outside of Raxm's control,
             * for example to a login page from a middleware, so we will just redirect
             * to that page.
             */
            if (response.redirected) {
                window.location.href = response.url
            }

            if (response.ok) {
                const responseText = await response.text();
    
                if (contentIsFromDump(responseText)) {
                    [dump, content] = splitDumpFromContent(responseText)
            
                    showHtmlModal(dump)
                    this.onError(message);

                } else {
                    this.onMessage(message, JSON.parse(responseText));
                }

            } else {
                handleErrorResponse(response, message, this);
            }

        } catch (error) {
            this.onError(message);
        }
    }
    
    buildHeaders(csrfToken, customHeaders) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'text/html, application/xhtml+xml',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Axm': true,

            ...(customHeaders),
        };
    
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
       
        return headers;
    }
    
    handleErrorResponse(response, message, context) {
        if (context.onError(message, response.status, response) === false) {
            return;
        }
    
        if (response.status === 419 && !store.sessionHasExpired) {
            store.sessionHasExpired = true;
            context.showExpiredMessage(response, message);
       
        } else {

            response.text().then(responseText => {
                showHtmlModal(responseText);
            });
        }
    }
    
}
