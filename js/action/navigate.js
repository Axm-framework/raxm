import Action from './index.js'

export default class extends Action {
    constructor(name, value, el) {
        super(el)

        this.type = 'navigate'
        this.name = name
        this.payload = {
            id: this.signature,
            name,
            value,
        }
    }
}
