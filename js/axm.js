import DOM from './dom/dom.js'
import './dom/polyfills/index.js'
import store from './Store.js'
import Connection from './connection/index.js'
import Polling from './component/Polling.js'
import Component from './component/index.js'
import dispatch from './util/dispatch.js'
import axmDirectives from './util/axm-directives.js'

import FileUploads from './component/FileUploads.js'
import LaravelEcho from './component/LaravelEcho.js'
import DirtyStates from './component/DirtyStates.js'
import DisableForms from './component/DisableForms.js'
import FileDownloads from './component/FileDownloads.js'
import LoadingStates from './component/LoadingStates.js'
import OfflineStates from './component/OfflineStates.js'
import SyncBrowserHistory from './component/SyncBrowserHistory.js'
import SupportAlpine from './component/SupportAlpine.js'
import SupportStacks from './component/SupportStacks.js'


class Axm {
    constructor() {
        this.connection = new Connection()
        this.components = store
        this.devToolsEnabled = false
        this.onLoadCallback = () => { }
        console.log('I ❤️ Axm')
        console.log('🚀')
    }

    first() {
        return Object.values(this.components.componentsById)[0].$axm
    }

    find(componentId) {
        return this.components.componentsById[componentId].$axm
    }

    all() {
        return Object.values(this.components.componentsById).map(
            component => component.$axm
        )
    }

    directive(name, callback) {
        this.components.registerDirective(name, callback)
    }

    hook(name, callback) {
        this.components.registerHook(name, callback)
    }

    onLoad(callback) {
        this.onLoadCallback = callback
    }

    onError(callback) {
        this.components.onErrorCallback = callback
    }

    emit(event, ...params) {
        this.components.emit(event, ...params)
    }

    emitTo(name, event, ...params) {
        this.components.emitTo(name, event, ...params)
    }

    on(event, callback) {
        this.components.on(event, callback)
    }

    addHeaders(headers) {
        this.connection.headers = { ...this.connection.headers, ...headers }
    }

    devTools(enableDevtools) {
        this.devToolsEnabled = enableDevtools
    }

    restart() {
        this.stop()
        this.start()
    }

    stop() {
        this.components.tearDownComponents()
    }

    start() {
        DOM.rootComponentElementsWithNoParents().forEach(el => {
            this.components.addComponent(new Component(el, this.connection))
        })

        this.onLoadCallback()
        dispatch('Axm:load')

        document.addEventListener(
            'visibilitychange',
            () => {
                this.components.AxmIsInBackground = document.hidden
            },
            false
        )

        this.components.initialRenderIsFinished = true
    }

    rescan(node = null) {
        DOM.rootComponentElementsWithNoParents(node).forEach(el => {
            const componentId = axmDirectives(el).get('id').value

            if (this.components.hasComponent(componentId)) return

            this.components.addComponent(new Component(el, this.connection))
        })
    }

    onPageExpired(callback) {
        this.components.sessionHasExpiredCallback = callback
    }
}

// SyncBrowserHistory()
SupportAlpine()
SupportStacks()
FileDownloads()
OfflineStates()
LoadingStates()
DisableForms()
FileUploads()
LaravelEcho()
DirtyStates()
Polling()

dispatch('Axm:available')

export default Axm