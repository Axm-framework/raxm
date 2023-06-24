import Axm from './../src/index.js'

window.Axm = new Axm()

queueMicrotask(() => {
    window.Axm.start()
})
