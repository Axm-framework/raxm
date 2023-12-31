var W = Object.create;
var m = Object.defineProperty,
  j = Object.defineProperties,
  F = Object.getOwnPropertyDescriptor,
  B = Object.getOwnPropertyDescriptors,
  $ = Object.getOwnPropertyNames,
  M = Object.getOwnPropertySymbols,
  Y = Object.getPrototypeOf,
  A = Object.prototype.hasOwnProperty,
  X = Object.prototype.propertyIsEnumerable;
var L = (i, e, t) =>
    e in i
      ? m(i, e, { enumerable: !0, configurable: !0, writable: !0, value: t })
      : (i[e] = t),
  y = (i, e) => {
    for (var t in e || (e = {})) A.call(e, t) && L(i, t, e[t]);
    if (M) for (var t of M(e)) X.call(e, t) && L(i, t, e[t]);
    return i;
  },
  f = (i, e) => j(i, B(e)),
  D = (i) => m(i, "__esModule", { value: !0 });
var z = (i, e) => {
    D(i);
    for (var t in e) m(i, t, { get: e[t], enumerable: !0 });
  },
  q = (i, e, t) => {
    if ((e && typeof e == "object") || typeof e == "function")
      for (let n of $(e))
        !A.call(i, n) &&
          n !== "default" &&
          m(i, n, {
            get: () => e[n],
            enumerable: !(t = F(e, n)) || t.enumerable,
          });
    return i;
  },
  h = (i) =>
    q(
      D(
        m(
          i != null ? W(Y(i)) : {},
          "default",
          i && i.__esModule && "default" in i
            ? { get: () => i.default, enumerable: !0 }
            : { value: i, enumerable: !0 }
        )
      ),
      i
    );
var r = (i, e, t) =>
  new Promise((n, s) => {
    var a = (l) => {
        try {
          p(t.next(l));
        } catch (g) {
          s(g);
        }
      },
      d = (l) => {
        try {
          p(t.throw(l));
        } catch (g) {
          s(g);
        }
      },
      p = (l) => (l.done ? n(l.value) : Promise.resolve(l.value).then(a, d));
    p((t = t.apply(i, e)).next());
  });
z(exports, { default: () => k });
var V = h(require("obsidian"));
var H = h(require("obsidian"));
function _(i, e) {
  let t = Object.assign({}, e, i);
  return Object.keys(t).reduce((n, s) => {
    let a = i[s],
      d = typeof a == "undefined" || a === null;
    return f(y({}, n), { [s]: d ? e[s] : a });
  }, {});
}
function N(i, e) {
  try {
    let t = (0, H.parseYaml)(i);
    return _(t, e);
  } catch (t) {
    return e;
  }
}
var c = {
  None: "",
  TOC: "[TOC]",
  _TOC_: "__TOC__",
  AzureWiki: "_TOC_",
  DevonThink: "<?=toc?>",
  TheBrain: "[/toc/]",
};
var P = {
    style: "bullet",
    min_depth: 2,
    max_depth: 6,
    externalStyle: "None",
    supportAllMatchers: !1,
    allow_inconsistent_headings: !1,
  },
  u = "dynamic-toc",
  R = `.${u}`,
  I = Object.keys(c);
var C = h(require("obsidian"));
var v = class {
  constructor(e) {
    this.cached = e;
  }
  get level() {
    return this.cached.level;
  }
  get rawHeading() {
    return this.cached.heading;
  }
  get isLink() {
    return /\[\[(.*?)\]\]/.test(this.cached.heading);
  }
  get href() {
    return this.isLink
      ? `#${this.parseMarkdownLink(this.rawHeading).split("|").join(" ")}`
      : null;
  }
  get markdownHref() {
    if (!this.isLink) return `[[#${this.rawHeading}]]`;
    let t = this.parseMarkdownLink(this.rawHeading).split("|");
    return t.length > 1 ? `[[#${t.join(" ")}|${t[1]}]]` : `[[#${t[0]}]]`;
  }
  parseMarkdownLink(e) {
    let [, t] = e.match(/\[\[(.*?)]\]/) || [];
    return t;
  }
};
function T(i, e) {
  if (!(i == null ? void 0 : i.headings)) return "";
  let { headings: t } = i,
    n = t.filter(
      (a) => !!a && a.level >= e.min_depth && a.level <= e.max_depth
    );
  if (!n.length) return "";
  let s = n.map((a) => new v(a));
  return e.style === "inline" ? J(s, e) : U(s, e);
}
function G(i, e, t) {
  let n = (t.style === "number" && "1.") || "-";
  return !t.varied_style || i.level === e
    ? n
    : t.style === "number"
    ? "-"
    : "1.";
}
function U(i, e) {
  let t = i[0].level,
    n = [];
  e.title && n.push(`${e.title}`);
  let s = 0;
  for (let a = 0; a < i.length; a++) {
    let d = i[a],
      p = G(d, t, e),
      l = new Array(Math.max(0, d.level - t));
    e.allow_inconsistent_headings &&
      (l.length - s > 1 && (l = new Array(s + 1)), (s = l.length));
    let g = l.fill("	").join("");
    n.push(`${g}${p} ${d.markdownHref}`);
  }
  return n.join(`
`);
}
function J(i, e) {
  let t = i.map((a) => a.level).reduce((a, d) => Math.min(a, d)),
    n = i.filter((a) => a.level === t),
    s = e.delimiter ? e.delimiter : "|";
  return n.map((a) => `${a.markdownHref}`).join(` ${s.trim()} `);
}
var S = class extends C.MarkdownRenderChild {
  constructor(e, t, n, s) {
    super(s);
    this.app = e;
    this.config = t;
    this.filePath = n;
    this.container = s;
    this.onActiveLeafChangeHandler = (e) => {
      let t = this.app.workspace.getActiveFile();
      (this.filePath = t.path), this.onFileChangeHandler(t);
    };
    this.onSettingsChangeHandler = (e) => {
      this.render(_(this.config, e));
    };
    this.onFileChangeHandler = (e) => {
      (this.filePath = e.path), !e.deleted && this.render();
    };
  }
  onload() {
    return r(this, null, function* () {
      yield this.render(),
        this.registerEvent(
          this.app.metadataCache.on(
            "dynamic-toc:settings",
            this.onSettingsChangeHandler
          )
        ),
        this.registerEvent(
          this.app.workspace.on(
            "active-leaf-change",
            this.onActiveLeafChangeHandler
          )
        ),
        this.registerEvent(
          this.app.metadataCache.on("changed", this.onFileChangeHandler)
        );
    });
  }
  render(e) {
    return r(this, null, function* () {
      this.container.empty(), this.container.classList.add(u);
      let t = T(
        this.app.metadataCache.getCache(this.filePath),
        e || this.config
      );
      yield C.MarkdownRenderer.renderMarkdown(
        t,
        this.container,
        this.filePath,
        this
      );
    });
  }
};
var o = h(require("obsidian"));
var O = class extends o.PluginSettingTab {
  constructor(e, t) {
    super(e, t);
    this.plugin = t;
  }
  display() {
    let { containerEl: e } = this;
    e.empty(),
      e.createEl("h2", { text: "Dynamic Table of Contents Settings" }),
      new o.Setting(e)
        .setName("List Style")
        .setDesc("The table indication")
        .addDropdown((n) =>
          n
            .addOptions({
              bullet: "Bullet",
              number: "Number",
              inline: "Inline",
            })
            .setValue(this.plugin.settings.style)
            .onChange((s) =>
              r(this, null, function* () {
                (this.plugin.settings.style = s),
                  yield this.plugin.saveSettings();
              })
            )
        ),
      new o.Setting(e)
        .setName("Enable varied style")
        .setDesc(
          "Varied style allows for the most top level heading to match your list style, then subsequent levels to be the opposite. For example if your list style is number, then your level 2 headings will be number, any levels lower then 2 will be bullet and vice versa."
        )
        .addToggle((n) =>
          n.setValue(this.plugin.settings.varied_style).onChange((s) =>
            r(this, null, function* () {
              (this.plugin.settings.varied_style = s),
                yield this.plugin.saveSettings();
            })
          )
        ),
      new o.Setting(e)
        .setName("Delimiter")
        .setDesc(
          "Only used when list style is inline. The delimiter between the list items"
        )
        .addText((n) =>
          n
            .setPlaceholder("e.g. -, *, ~")
            .setValue(this.plugin.settings.delimiter)
            .onChange((s) =>
              r(this, null, function* () {
                (this.plugin.settings.delimiter = s),
                  this.plugin.saveSettings();
              })
            )
        ),
      new o.Setting(e)
        .setName("Minimum Header Depth")
        .setDesc("The default minimum header depth to render")
        .addSlider((n) =>
          n
            .setLimits(1, 6, 1)
            .setValue(this.plugin.settings.min_depth)
            .setDynamicTooltip()
            .onChange((s) =>
              r(this, null, function* () {
                s > this.plugin.settings.max_depth
                  ? new o.Notice("Min Depth is higher than Max Depth")
                  : ((this.plugin.settings.min_depth = s),
                    yield this.plugin.saveSettings());
              })
            )
        ),
      new o.Setting(e)
        .setName("Maximum Header Depth")
        .setDesc("The default maximum header depth to render")
        .addSlider((n) =>
          n
            .setLimits(1, 6, 1)
            .setValue(this.plugin.settings.max_depth)
            .setDynamicTooltip()
            .onChange((s) =>
              r(this, null, function* () {
                s < this.plugin.settings.min_depth
                  ? new o.Notice("Max Depth is higher than Min Depth")
                  : ((this.plugin.settings.max_depth = s),
                    yield this.plugin.saveSettings());
              })
            )
        ),
      new o.Setting(e)
        .setName("Title")
        .setDesc(
          "The title of the table of contents, supports simple markdown such as ## Contents or **Contents**"
        )
        .addText((n) =>
          n
            .setPlaceholder("## Table of Contents")
            .setValue(this.plugin.settings.title)
            .onChange((s) =>
              r(this, null, function* () {
                (this.plugin.settings.title = s), this.plugin.saveSettings();
              })
            )
        );
    let t = new o.Setting(e)
      .setName("External rendering support")
      .setDesc(
        "Different markdown viewers provided Table of Contents support such as [TOC] or [[_TOC_]]. You may need to restart Obsidian for this to take effect."
      )
      .addDropdown((n) =>
        n
          .addOptions(
            Object.keys(c).reduce((s, a) => {
              let d = c[a];
              return f(y({}, s), { [a]: d });
            }, {})
          )
          .setDisabled(this.plugin.settings.supportAllMatchers)
          .setValue(this.plugin.settings.externalStyle)
          .onChange((s) =>
            r(this, null, function* () {
              (this.plugin.settings.externalStyle = s),
                yield this.plugin.saveSettings();
            })
          )
      );
    new o.Setting(e)
      .setName("Support all external renderers")
      .setDesc("Cannot be used in conjunction with individual renderers")
      .addToggle((n) =>
        n.setValue(this.plugin.settings.supportAllMatchers).onChange((s) =>
          r(this, null, function* () {
            (this.plugin.settings.supportAllMatchers = s),
              t.setDisabled(s),
              yield this.plugin.saveSettings();
          })
        )
      ),
      new o.Setting(e)
        .setName("Allow inconsistent heading levels")
        .setDesc(
          "NOT RECOMMENDED (may be removed in future): If enabled, the table of contents will be generated even if the header depth is inconsistent. This may cause the table of contents to be rendered incorrectly."
        )
        .addToggle((n) =>
          n
            .setValue(this.plugin.settings.allow_inconsistent_headings)
            .onChange((s) =>
              r(this, null, function* () {
                (this.plugin.settings.allow_inconsistent_headings = s),
                  yield this.plugin.saveSettings();
              })
            )
        );
  }
};
var b = h(require("obsidian"));
var w = class extends b.MarkdownRenderChild {
  constructor(e, t, n, s, a) {
    super(s);
    this.app = e;
    this.settings = t;
    this.filePath = n;
    this.match = a;
    this.onSettingsChangeHandler = () => {
      this.render();
    };
    this.onFileChangeHandler = (e) => {
      e.deleted || e.path !== this.filePath || this.render();
    };
  }
  static findMatch(e, t) {
    return (
      Array.from(e.querySelectorAll("p, span, a")).find((s) =>
        s.textContent.toLowerCase().includes(t.toLowerCase())
      ) || null
    );
  }
  onload() {
    return r(this, null, function* () {
      this.render(),
        this.registerEvent(
          this.app.metadataCache.on(
            "dynamic-toc:settings",
            this.onSettingsChangeHandler
          )
        ),
        this.registerEvent(
          this.app.metadataCache.on("changed", this.onFileChangeHandler)
        );
    });
  }
  render() {
    return r(this, null, function* () {
      let e = T(this.app.metadataCache.getCache(this.filePath), this.settings),
        t = document.createElement("div");
      t.classList.add(u),
        yield b.MarkdownRenderer.renderMarkdown(e, t, this.filePath, this),
        (this.match.style.display = "none");
      let n = this.containerEl.querySelector(R);
      n && this.containerEl.removeChild(n),
        this.match.parentNode.appendChild(t);
    });
  }
};
var K = h(require("obsidian")),
  E = {
    "code-block": { value: "```toc\n```", label: "Code block" },
    TOC: { value: "[TOC]", label: "[TOC]" },
    _TOC_: { label: "__TOC__", value: "[[__TOC__]]" },
    AzureWiki: { label: "_TOC_", value: "[[_TOC_]]" },
    DevonThink: { label: "<?=toc?>", value: "<?=toc?>" },
    TheBrain: { label: "[/toc/]", value: "[/toc/]" },
  },
  x = class extends K.FuzzySuggestModal {
    constructor(e, t) {
      super(e);
      (this.app = e),
        (this.plugin = t),
        this.setPlaceholder("Type name of table of contents type...");
    }
    getItems() {
      return this.plugin.settings.supportAllMatchers
        ? Object.keys(E)
        : this.plugin.settings.externalStyle !== "None"
        ? ["code-block", this.plugin.settings.externalStyle]
        : ["code-block"];
    }
    getItemText(e) {
      let t = Object.keys(E).find((n) => n === e);
      return E[t].label;
    }
    onChooseItem(e) {
      this.callback(E[e].value);
    }
    start(e) {
      (this.callback = e), this.open();
    }
  };
var k = class extends V.Plugin {
  constructor() {
    super(...arguments);
    this.onload = () =>
      r(this, null, function* () {
        yield this.loadSettings(),
          this.addSettingTab(new O(this.app, this)),
          this.addCommand({
            id: "dynamic-toc-insert-command",
            name: "Insert Table of Contents",
            editorCallback: (e) => {
              new x(this.app, this).start((n) => {
                e.setCursor(e.getCursor().line, 0), e.replaceSelection(n);
              });
            },
          }),
          this.registerMarkdownCodeBlockProcessor("toc", (e, t, n) => {
            let s = N(e, this.settings);
            n.addChild(new S(this.app, s, n.sourcePath, t));
          }),
          this.registerMarkdownPostProcessor((e, t) => {
            let n =
              this.settings.supportAllMatchers === !0
                ? I
                : [this.settings.externalStyle];
            for (let s of n) {
              if (!s || s === "None") continue;
              let a = w.findMatch(e, c[s]);
              !(a == null ? void 0 : a.parentNode) ||
                t.addChild(new w(this.app, this.settings, t.sourcePath, e, a));
            }
          });
      });
    this.loadSettings = () =>
      r(this, null, function* () {
        this.settings = Object.assign({}, P, yield this.loadData());
      });
    this.saveSettings = () =>
      r(this, null, function* () {
        yield this.saveData(this.settings),
          this.app.metadataCache.trigger("dynamic-toc:settings", this.settings);
      });
  }
};
0 && (module.exports = {});
