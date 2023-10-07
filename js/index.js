// import { on, trigger } from './events.js'
import { directive } from './directives.js'
import { start, stop, rescan } from './boot.js'
// import './dom/polyfills/index.js'
// import Polling from './component/Polling.js'
import FileUploads from './component/FileUploads.js'
import LaravelEcho from './component/LaravelEcho.js'
import DirtyStates from './component/DirtyStates.js'
// import SyncBrowserHistory from './component/SyncBrowserHistory.js'
import SupportAlpine from './component/SupportAlpine.js'
import { find, first, getByName, all, on, trigger, hook } from './store.js'


let Raxm = {
    directive,
    start, stop, rescan,
    find, first, getByName, all, on, trigger, hook,
}

if (window.Raxm) console.warn('Detected multiple instances of Raxm running')
if (window.Alpine) console.warn('Detected multiple instances of Alpine running')

// Register support...
import './features/index.js'

// Register directives...
import './directives/index.js'

// SyncBrowserHistory()
SupportAlpine()
// SupportStacks()
// DisableForms()
FileUploads()
LaravelEcho()
DirtyStates()
// Polling()


if (window.Raxm === undefined) {
    document.addEventListener('DOMContentLoaded', () => {
        window.Raxm = Raxm       
        // Start Raxm...
        Raxm.start()
    })
}

export { Raxm }
