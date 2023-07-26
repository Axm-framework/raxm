import store from '../Store.js'
import getCsrfToken from '../util/getCsrfToken.js'
import { showHtmlModal } from '../util/modal.js'

export default class Connection {
    constructor() {
        this.headers = {}

    }

    onMessage(message, payload) {
        message.component.receiveMessage(message, payload)
    }

    onError(message, status, response) {
        message.component.messageSendFailed()

        return store.onErrorCallback(status, response)
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
        let payload   = message.payload()
        let csrfToken = getCsrfToken()
        let socketId  = this.getSocketId()

        if (window.__testing_request_interceptor) {
            return window.__testing_request_interceptor(payload, this)
        }

        let url = window.raxm_app_url;

        try {
            
            const response = await fetch(

                `${url}/${payload.fingerprint.name}`,
                {
                    method: 'POST',
                    body: JSON.stringify(payload),
                    // This enables "cookies".
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/html, application/xhtml+xml',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Axm': true,

                        // set Custom Headers
                        ...(this.headers),

                        // We'll set this explicitly to mitigate potential interference from ad-blockers/etc.
                        'Referer': window.location.href,
                        ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
                        ...(socketId  && { 'X-Socket-ID' : socketId  })
                    },
                }
            )

            if (response.ok) {
                const responseText = await response.text();
              
                if (this.isOutputFromDump(responseText)) {
                    this.onError(message);
                    showHtmlModal(responseText);
                } else {
                    this.onMessage(message, JSON.parse(responseText));
                }

            } else {
                await this.handleErrorResponse(response, message);
            }

        } catch (error) {
            this.onError(message)
        }
    }

    async handleErrorResponse(response, message) {
        const { status }    = response;
        const responseBody  = await response.text();
        const onErrorResult = this.store.onErrorCallback(status, responseBody);
      
        if (onErrorResult === false) return;
    
        if (status === 419) {
            if (this.store.sessionHasExpired) return;
    
            this.store.sessionHasExpired = true;
            this.showExpiredMessage(response, message);

        } else {
            showHtmlModal(responseBody);
        }
    }
    
    isOutputFromDump(output) {
        return !!output.match(/<script>Sfdump\(".+"\)<\/script>/)
    }

    getSocketId() {
        if (typeof Echo !== 'undefined') {
            return Echo.socketId()
        }
    }
}