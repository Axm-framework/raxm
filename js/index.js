/**
 *           __
 *     __    \  \     __   ___ ___     (_)
 *    /  \    \  \  /  / /  _   _  \    _  ___
 *   / /\ \    \  \/  /  | | | | | |   | |/ __|
 *  / ____ \   /  /\  \  | | | | | |   | |\__ \
 * /_/    \_\ /  /  \__\ |_| \_/ |_|(_)| ||___/
 *           /_ /                    _/  |
 *                                  |___/
 *
 * Let's build Axm together. It's easier than you think.
 * For starters, we'll import Axm's core. This is the
 * object that will expose all of Axm's public API.
 */
import Axm from './axm.js'



/**
 * _______________________________________________________
 * The Evaluator
 * -------------------------------------------------------
 *
 * Now we're ready to bootstrap Axm's evaluation system.
 * It's the function that converts raw JavaScript string
 * expressions like @click="toggle()", into actual JS.
 */
// import { normalEvaluator } from './evaluator'
//    import './util/axm-directives.js'
// Axm.setEvaluator(normalEvaluator)

/**
 * _______________________________________________________
 * The Reactivity Engine
 * -------------------------------------------------------
 *
 * This is the reactivity core of Axm. It's the part of
 * Axm that triggers an element with x-text="message"
 * to update its inner text when "message" is changed.
 */
// import { reactive, effect, stop, toRaw } from '@vue/reactivity'

// Axm.setReactivityEngine({ reactive, effect, release: stop, raw: toRaw })

/**
 * _______________________________________________________
 * The Magics
 * -------------------------------------------------------
 *
 * Yeah, we're calling them magics here like they're nouns.
 * These are the properties that are magically available
 * to all the Axm expressions, within your web app.
 */
// import './magics/index'

/**
 * _______________________________________________________
 * The Directives
 * -------------------------------------------------------
 *
 * Now that the core is all set up, we can register Axm
 * directives like x-text or x-on that form the basis of
 * how Axm adds behavior to an app's static markup.
 */
// import './directives/index'

/**
 * _______________________________________________________
 * The Axm Global
 * -------------------------------------------------------
 *
 * Now that we have set everything up internally, anything
 * Axm-related that will need to be accessed on-going
 * will be made available through the "Axm" global.
 */
export default Axm

// window.Axm = new Axm();
// document.addEventListener("DOMContentLoaded", () => {
//     window.Axm.start();
// })
