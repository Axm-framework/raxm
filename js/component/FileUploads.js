import store from '../store.js'

export default function () {
    store.registerHook('interceptRaxmModelAttachListener', (directive, el, component, property) => {
        if (! (el.tagName === 'INPUT' && el.type === 'file')) return

        let start  = () => el.dispatchEvent(new CustomEvent('raxm-upload-start',  { bubbles: true, detail: { id: component.id, property}}))
        let finish = () => el.dispatchEvent(new CustomEvent('raxm-upload-finish', { bubbles: true, detail: { id: component.id, property}}))
        let error  = () => el.dispatchEvent(new CustomEvent('raxm-upload-error',  { bubbles: true, detail: { id: component.id, property}}))
        
        let progress = (progressEvent) => {
            var percentCompleted = Math.round( (progressEvent.loaded * 100) / progressEvent.total )

            el.dispatchEvent(
                new CustomEvent('raxm-upload-progress', {
                    bubbles: true, detail: { progress: percentCompleted }
                })
            )
        }

        let eventHandler = e => {
            if (e.target.files.length === 0) return

            start()

            if (e.target.multiple) {
                component.uploadMultiple(directive.value, e.target.files, finish, error, progress)
            } else {
                component.upload(directive.value, e.target.files[0], finish, error, progress)
            }
        }

        el.addEventListener('change', eventHandler)

        // There's a bug in browsers where selecting a file, removing it,
        // then re-adding it doesn't fire the change event. This fixes it.
        // Reference: https://stackoverflow.com/questions/12030686/html-input-file-selection-event-not-firing-upon-selecting-the-same-file
        let clearFileInputValue = () => { el.value = null }
        el.addEventListener('click', clearFileInputValue)

        component.addListenerForTeardown(() => {
            el.removeEventListener('change', eventHandler)
            el.removeEventListener('click', clearFileInputValue)
        })
    })
}
