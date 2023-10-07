// import { directive, PREFIX_REGEX } from "../directives.js"
// import { Raxm } from "../index.js"
// import Message from '../Message.js'
// import store from '../store.js'


// // Directiva para links de navegación
// directive('navigate', ({ el, directive, component }) => {

//     el.addEventListener('click', handleNavigate)
// })

// const MAX_HISTORY_LENGTH = 10 // Define el límite de historial
// let oldBodyScriptTagHashes = []
// let attributesExemptFromScriptTagHashing = ['data-csrf']


// // Handler de navegación
// function handleNavigate(event) {
 
//     if (shouldInterceptClick(event)) return
//     event.preventDefault()

//     // Guarda el estado actual antes de navegar
//     updateHistoryStateForCurrentPage()

//     // Navega a la nueva URL
//     const newUrl = event.target.getAttribute('href')
//     navigateTo(newUrl)
// }

// // Manejador PopState
// window.addEventListener('popstate', e => {
//     const state = e.state
//     if (state && state.raxm && state.raxm._html) {
//         // Recupera el estado almacenado en el historial y renderiza la vista
//         renderView(fromSessionStorage(state.raxm._html))
//     } else {
//         // Si no hay un estado válido en el historial, navega a la URL actual
//         navigateTo(window.location.href)
//     }

//     dispatchEvent(new Event('raxm:popstate'))
//     window.Raxm.start()
// })

// // Navegar a una URL
// export async function navigateTo(url) {
//     const newUrl = new URL(url, document.baseURI)

//     // Verifica si la URL actual es igual a la nueva URL
//     if (window.location.href === newUrl.href) {
//         // Recupera el estado almacenado en el historial y renderiza la vista
//         const state = history.state
//         if (state && state.raxm && state.raxm._html) {
//             renderView(fromSessionStorage(state.raxm._html))
//             return
//         }
//     }

//     // Cargar vista
//     const response = await loadView(url)

//     const pageState = { html: response.html }
//     const urlObject = new URL(url, document.baseURI)  // Actualiza el objeto de URL nuevamente

//     // Utiliza pushState para agregar una nueva entrada al historial
//     pushState(urlObject, pageState.html)

//     renderView(response.html)
// }

// // Cargar vista 
// async function loadView(url) {
//     document.dispatchEvent(new Event('raxm:navigating'))
   
//     return fetch(url) 
//     .then(res => res.text())
//     .then(html => {
//         return { html }
//     })
// }

// async function renderView(html) {
//     let newDocument = (new DOMParser()).parseFromString(html, "text/html")
//     let newBody = document.adoptNode(newDocument.body)
//     let newHead = document.adoptNode(newDocument.head)

//     oldBodyScriptTagHashes = oldBodyScriptTagHashes.concat(Array.from(document.body.querySelectorAll('script')).map(i => {
//         return simpleHash(ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing))
//     }))

//     mergeNewHead(newHead)

//     prepNewBodyScriptTagsToRun(newBody, oldBodyScriptTagHashes)

//     let oldBody = document.body

//     document.body.replaceWith(newBody) 
//     document.dispatchEvent(new CustomEvent('raxm:navigated', {detail: {visit: { completed: true }}}))  
// }

// function shouldInterceptClick(event) {
// 	return (
// 		event.which > 1 ||
// 		event.altKey  ||
// 		event.ctrlKey ||
// 		event.metaKey ||
// 		event.shiftKey
// 	)
// }

// function updateHistoryStateForCurrentPage() {
//     const currentPageUrl = new URL(window.location.href, document.baseURI)
//     const currentState = {
//         html: document.documentElement.outerHTML
//     }

//     pushState(currentPageUrl, currentState.html)
// }

// export function pushState(url, html) {
//     updateState('pushState', url, html)
// }

// export function replaceState(url, html) {
//     updateState('replaceState', url, html)
// }

// function updateState(method, url, html) {
//     clearState() 

//     let key = (new Date).getTime()

//     tryToStoreInSession(key, html)

//     let state = history.state || {}

//     if (! state.raxm) state.raxm = {}

//     state.raxm._html = key

//     try {
//         // 640k character limit:
//         history[method](state, document.title, url)
//     } catch (error) {
//         if (error instanceof DOMException && error.name === 'SecurityError') {
//             console.error('Raxm: You can\'t use axm:navigate with a link to a different root domain: '+url)
//         }
//     }
// }

// function clearState() {
   
// // Función para verificar y limpiar el historial si es necesario
//     const currentHistory = window.history.state || {}
//     const historyData = currentHistory.raxm || []

//     if (historyData.length >= MAX_HISTORY_LENGTH) {
//         // Elimina el elemento más antiguo del historial
//         window.history.go(-1)

//         // Limpia el registro local de historial
//         historyData.shift() // Elimina el elemento más antiguo
//         currentHistory.raxm = historyData

//         // Reemplaza el estado del historial
//         window.history.replaceState(currentHistory, document.title, window.location.href)
//     }
// }
   
// function fromSessionStorage(timestamp) {
//     let state = JSON.parse(sessionStorage.getItem('raxm:'+timestamp))

//     return state
// }

// function tryToStoreInSession(timestamp, value) {
//     // sessionStorage has a max storage limit (usally 5MB).
//     // If we meet that limit, we'll start removing entries
//     // (oldest first), until there's enough space to store
//     // the new one.
//     try {
//         sessionStorage.setItem('raxm:'+timestamp, JSON.stringify(value))
//     } catch (error) {
//         // 22 is Chrome, 1-14 is other browsers.
//         if (! [22, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14].includes(error.code)) return

//         let oldestTimestamp = Object.keys(sessionStorage)
//             .map(key => Number(key.replace('raxm:', '')))
//             .sort()
//             .shift()

//         if (! oldestTimestamp) return

//         sessionStorage.removeItem('raxm:'+oldestTimestamp)

//         tryToStoreInSession(timestamp, value)
//     }
// }

// function simpleHash(str) {
//     return str.split('').reduce((a, b) => {
//         a = ((a << 5) - a) + b.charCodeAt(0)

//         return a & a
//     }, 0)
// }

// function ignoreAttributes(subject, attributesToRemove) {
//     let result = subject

//     attributesToRemove.forEach(attr => {
//         // Create a regex pattern to match the attribute and its value.
//         // The regex handles attributes that have values surrounded by either single or double quotes.
//         const regex = new RegExp(`${attr}="[^"]*"|${attr}='[^']*'`, 'g')

//         result = result.replace(regex, '')
//     })

//     return result.trim()
// }

// function mergeNewHead(newHead) {
//     let children = Array.from(document.head.children)
//     let headChildrenHtmlLookup = children.map(i => i.outerHTML)

//     // Only add scripts and styles that aren't already loaded on the page.
//     let garbageCollector = document.createDocumentFragment()

//     let touchedHeadElements = []
//     for (let child of Array.from(newHead.children)) {
//         if (isAsset(child)) {
//             if (! headChildrenHtmlLookup.includes(child.outerHTML)) {
//                 if (isTracked(child)) {
//                     if (ifTheQueryStringChangedSinceLastRequest(child, children)) {
//                         setTimeout(() => window.location.reload())
//                     }
//                 }

//                 if (isScript(child)) {
//                     document.head.appendChild(DOM.cloneScriptTag(child))
//                 } else {
//                     document.head.appendChild(child)
//                 }
//             } else {
//                 garbageCollector.appendChild(child)
//             }

//             touchedHeadElements.push(child)
//         }
//     }

//     // Remove any assets that aren't on the new page...
//     // @todo: Re-enable this code and find a better way to managed injected stylesheets. See raxm/raxm#6824
//     // for (let child of Array.from(document.head.children)) {
//     //     if (isAsset(child)) {
//     //         if (! touchedHeadElements.some(i => i.outerHTML === child.outerHTML)) {
//     //             child.remove()
//     //         }
//     //     }
//     // }

//     // How to free up the garbage collector?

//     // Remove existing non-asset elements like meta, base, title, template.
//     for (let child of Array.from(document.head.children)) {
//         if (! isAsset(child)) child.remove()
//     }

//     // Add new non-asset elements left over in the new head element.
//     for (let child of Array.from(newHead.children)) {
//         document.head.appendChild(child)
//     }
// }

// function prepNewBodyScriptTagsToRun(newBody, oldBodyScriptTagHashes) {
//     newBody.querySelectorAll('script').forEach(i => {
//         // We don't want to re-run script tags marked as "data-navigate-once"...
//         if (i.hasAttribute('data-navigate-once')) {
//             // However, if they didn't exist on the previous page, we do.
//             // Therefore, we'll check the "old body script hashes" to
//             // see if it was already there before skipping it...

//             let hash = simpleHash(
//                 ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing)
//             )

//             if (oldBodyScriptTagHashes.includes(hash)) return
//         }

//         i.replaceWith(DOM.cloneScriptTag(i))
//     })
// }

// function DOM.cloneScriptTag(el) {
//     let script = document.createElement('script')

//     script.textContent = el.textContent
//     script.async = el.async

//     for (let attr of el.attributes) {
//         script.setAttribute(attr.name, attr.value)
//     }

//     return script
// }

// function isAsset(el) {
//     return (el.tagName.toLowerCase() === 'link' && el.getAttribute('rel').toLowerCase() === 'stylesheet')
//         ||  el.tagName.toLowerCase() === 'style'
//         ||  el.tagName.toLowerCase() === 'script'
// }

// function isTracked(el) {
//     return el.hasAttribute('data-navigate-track')
// }

// function isScript(el)   {
//     return el.tagName.toLowerCase() === 'script'
// }





import { directive, PREFIX_REGEX } from "../directives.js"
import DOM from "../dom/dom.js"

directive('navigate', ({ el, directive, component }) => {
    el.addEventListener('click', navigationManager.handleNavigate)
})

const MAX_HISTORY_LENGTH = 10
const attributesExemptFromScriptTagHashing = ['data-csrf']

class NavigationManager {
    constructor() {
        this.oldBodyScriptTagHashes = []
        this.handleNavigate = this.handleNavigate.bind(this);
        window.addEventListener('popstate', this.handlePopState.bind(this))
    }

    handleNavigate(event) {
        if (this.shouldInterceptClick(event)) return
        event.preventDefault()

        this.updateHistoryStateForCurrentPage()
        const newUrl = event.target.getAttribute('href')
        this.navigateTo(newUrl)
    }

    shouldInterceptClick(event) {
        return (
            event.which > 1 ||
            event.altKey  ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey
        )
    }

    async navigateTo(url) {
        const newUrl = new URL(url, document.baseURI)

        // Verifica si la URL actual es igual a la nueva URL
        if (window.location.href === newUrl.href) {
            // Recupera el estado almacenado en el historial y renderiza la vista
            const state = history.state
            if (state && state.raxm && state.raxm._html) {
                renderView(fromSessionStorage(state.raxm._html))
                return
            }
        }

        // Cargar vista
        const response = await loadView(url)

        const pageState = { html: response.html }
        const urlObject = new URL(url, document.baseURI)  // Actualiza el objeto de URL nuevamente

        // Utiliza pushState para agregar una nueva entrada al historial
        this.pushState(urlObject, pageState.html)

        renderView(response.html)
    }

    handlePopState(e) {
        const state = e.state
        if (state && state.raxm && state.raxm._html) {
            renderView(this.fromSessionStorage(state.raxm._html))
        } else {
            this.navigateTo(window.location.href)
        }
        dispatchEvent(new Event('raxm:popstate'))
        window.Raxm.start()
    }

    updateHistoryStateForCurrentPage() {
        const currentPageUrl = new URL(window.location.href, document.baseURI)
        const currentState = {
            html: document.documentElement.outerHTML
        }
        this.pushState(currentPageUrl, currentState.html)
    }

    pushState(url, html) {
        this.updateState('pushState', url, html)
    }

    replaceState(url, html) {
        this.updateState('replaceState', url, html)
    }

    updateState(method, url, html) {
        this.clearState()

        let key = (new Date).getTime()
        this.tryToStoreInSession(key, html)
        let state = history.state || {}
        if (!state.raxm) state.raxm = {}
        state.raxm._html = key
        try {
            // 640k character limit:
            history[method](state, document.title, url)
        } catch (error) {
            if (error instanceof DOMException && error.name === 'SecurityError') {
                console.error('Raxm: You can\'t use axm:navigate with a link to a different root domain: ' + url)
            }
        }
    }

    clearState() {
        const currentHistory = window.history.state || {}
        const historyData = currentHistory.raxm || []
        if (historyData.length >= MAX_HISTORY_LENGTH) {
            window.history.go(-1)
            historyData.shift()
            currentHistory.raxm = historyData
            window.history.replaceState(currentHistory, document.title, window.location.href)
        }
    }

    fromSessionStorage(timestamp) {
        let state = JSON.parse(sessionStorage.getItem('raxm:' + timestamp))
        return state
    }

    tryToStoreInSession(timestamp, value) {
        try {
            sessionStorage.setItem('raxm:' + timestamp, JSON.stringify(value))
        } catch (error) {
            if (![22, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14].includes(error.code)) return
            let oldestTimestamp = Object.keys(sessionStorage)
                .map(key => Number(key.replace('raxm:', '')))
                .sort()
                .shift()
            if (!oldestTimestamp) return
            sessionStorage.removeItem('raxm:' + oldestTimestamp)
            this.tryToStoreInSession(timestamp, value)
        }
    }
}

const navigationManager = new NavigationManager()


// Cargar vista 
async function loadView(url) {
    document.dispatchEvent(new Event('raxm:navigating'))
    try {
        const response = await fetch(url)
        const html = await response.text()
        return { html }
    } catch (error) {
        console.error('Error loading view:', error)
        return { html: '' }
    }
}

async function renderView(html) {
    const newDocument = (new DOMParser()).parseFromString(html, "text/html")
    const newBody = document.adoptNode(newDocument.body)
    const newHead = document.adoptNode(newDocument.head)
    const newBodyScriptTagHashes = Array.from(newBody.querySelectorAll('script')).map(i => {
        return simpleHash(DOM.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing))
    })
    mergeNewHead(newHead)
    prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes)
    const oldBody = document.body
    document.body.replaceWith(newBody)
    document.dispatchEvent(new CustomEvent('raxm:navigated', { detail: { visit: { completed: true } } }))
}

function simpleHash(str) {
    return str.split('').reduce((a, b) => {
        a = ((a << 5) - a) + b.charCodeAt(0)
        return a & a
    }, 0)
}

function mergeNewHead(newHead) {
    const children = Array.from(document.head.children)
    const headChildrenHtmlLookup = children.map(i => i.outerHTML)
    const garbageCollector = document.createDocumentFragment()
    const touchedHeadElements = []
    for (const child of Array.from(newHead.children)) {
        if (DOM.isAsset(child)) {
            if (!headChildrenHtmlLookup.includes(child.outerHTML)) {
                if (isTracked(child)) {
                    if (ifTheQueryStringChangedSinceLastRequest(child, children)) {
                        setTimeout(() => window.location.reload())
                    }
                }
                if (DOM.isScript(child)) {
                    document.head.appendChild(DOM.cloneScriptTag(child))
                } else {
                    document.head.appendChild(child)
                }
            } else {
                garbageCollector.appendChild(child)
            }
            touchedHeadElements.push(child)
        }
    }
    for (const child of Array.from(document.head.children)) {
        if (! DOM.isAsset(child)) child.remove()
    }
    for (const child of Array.from(newHead.children)) {
        document.head.appendChild(child)
    }
}

function ifTheQueryStringChangedSinceLastRequest(el, currentHeadChildren) {
    let [uri, queryString] = extractUriAndQueryString(el)

    return currentHeadChildren.some(child => {
        if (! isTracked(child)) return false

        let [currentUri, currentQueryString] = extractUriAndQueryString(child)

        // Only consider a data-navigate-track element changed if the query string has changed (not the URI)...
        if (currentUri === uri && queryString !== currentQueryString) return true
    })
}

function extractUriAndQueryString(el) {
    let url = DOM.isScript(el) ? el.src : el.href

    return url.split('?')
}

function prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes) {
    newBody.querySelectorAll('script').forEach(i => {
        if (i.hasAttribute('data-navigate-once')) {
            let hash = simpleHash(
                DOM.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing)
            )
            if (newBodyScriptTagHashes.includes(hash)) return
        }
        i.replaceWith(DOM.cloneScriptTag(i))
    })
}

function isTracked(el) {
    return el.hasAttribute('data-navigate-track')
}

export function navigateTo(url) {
    navigationManager.navigateTo(url)
}
