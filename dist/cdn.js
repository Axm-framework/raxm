import Axm from './../js/index.js'

window.Axm = new Axm()

queueMicrotask(() => {
    window.Axm.start()
})
