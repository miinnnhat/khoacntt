!function(){var r=/\s/g,a=/>/g,d=/</g;var f="__autosizeInputGhost";function l(){var t=document.createElement("div");return t.id=f,t.style.cssText="display:inline-block;height:0;overflow:hidden;position:absolute;top:0;visibility:hidden;white-space:nowrap;",document.body.appendChild(t),t}var s="";document.addEventListener("DOMContentLoaded",function(){s=l()}),window.tfAutosizeInput=function(n,t){var e=window.getComputedStyle(n),i="box-sizing:"+e.boxSizing+";border-left:"+e.borderLeftWidth+" solid black;border-right:"+e.borderRightWidth+" solid black;font-family:"+e.fontFamily+";font-feature-settings:"+e.fontFeatureSettings+";font-kerning:"+e.fontKerning+";font-size:"+e.fontSize+";font-stretch:"+e.fontStretch+";font-style:"+e.fontStyle+";font-variant:"+e.fontVariant+";font-variant-caps:"+e.fontVariantCaps+";font-variant-ligatures:"+e.fontVariantLigatures+";font-variant-numeric:"+e.fontVariantNumeric+";font-weight:"+e.fontWeight+";letter-spacing:"+e.letterSpacing+";margin-left:"+e.marginLeft+";margin-right:"+e.marginRight+";padding-left:"+e.paddingLeft+";padding-right:"+e.paddingRight+";text-indent:"+e.textIndent+";text-transform:"+e.textTransform;function o(t){t=t||n.value||n.getAttribute("placeholder")||"",(s=null===document.getElementById(f)?l():s).style.cssText+=i,s.innerHTML=t.replace(r,"&nbsp;").replace(a,"&lt;").replace(d,"&gt;");t=window.getComputedStyle(s).width;return n.style.display="0px"===t?"none":"",n.style.width=t}return n.addEventListener("input",function(){o()}),e=o(),t&&t.minWidth&&"0px"!==e&&(n.style.minWidth=e),o}}((window,document));

