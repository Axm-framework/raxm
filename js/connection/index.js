import store from '../Store.js'
import componentStore from '../Store.js'
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
        let payload   = message.payload()
        let csrfToken = getCsrfToken()
        let socketId  = this.getSocketId()

        if (window.__testing_request_interceptor) {
            return window.__testing_request_interceptor(payload, this)
        }

        let url = window.raxm_app_url;

        // Forward the query string for the ajax requests.
        fetch(

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
            .then(response => {
                if (response.ok) {
                    response.text().then(response => {
                        if (this.isOutputFromDump(response)) {
                            this.onError(message)
                            showHtmlModal(response)
                        } else {

                            this.onMessage(message, JSON.parse(response))

                        }
                    })
                } else {
                    if (this.onError(message, response.status, response) === false) return

                    if (response.status === 419) {
                        if (store.sessionHasExpired) return

                        store.sessionHasExpired = true

                        this.showExpiredMessage(response, message)
                    } else {
                        response.text().then(response => {
                            showHtmlModal(response)
                        })
                    }
                }
            })
            .catch(() => {
                this.onError(message)
            })
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