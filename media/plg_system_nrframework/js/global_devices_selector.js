var TF_Global_Devices_Selector=function(){function e(){this.initEvents()}var t=e.prototype;return t.initEvents=function(){document.addEventListener("click",function(e){this.onChange(e)}.bind(this))},t.onChange=function(e){e=e.target.closest(".tf-global-devices-selector--items--item:not(.is-active)");e&&(e.parentElement.querySelector(".is-active").classList.remove("is-active"),e.classList.add("is-active"),TF_Responsive_Controls.changeAllResponsiveControlsBreakpoints(e.dataset.breakpoint))},e}();document.addEventListener("DOMContentLoaded",function(){new TF_Global_Devices_Selector});

