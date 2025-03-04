var TF_Google_Map=function(){function t(t){if(t){if(this.ref=t,this.wrapper=null,this.map_element=null,this.map=null,this.options={},this.defaults={lat:0,long:0,zoom:5,view:"roadmap",scale:!1,enable_info_window:!1,markers:[],markerImage:""},this.ref instanceof HTMLElement)this.initWithDataAttributes();else{if(!(this.ref instanceof Object))return;this.initWithOptions(this.ref)}this.markersBounds=new google.maps.LatLngBounds,this.defaultZoom=parseInt(this.options.zoom)||15}}var e=t.prototype;return e.initWithDataAttributes=function(){this.wrapper=this.ref,this.map_element=this.wrapper.querySelector(".map-item"),this.initWithOptions(JSON.parse(this.wrapper.dataset.options))},e.initWithOptions=function(t){void 0===t&&(t={}),this.options=Object.assign({},this.defaults,t);t=this.options.value.split(",");this.options.lat=parseFloat(t[0])||this.options.lat,this.options.long=parseFloat(t[1])||this.options.long,this.wrapper||(this.wrapper=document.querySelector(".nrf-widget.googlemap#"+this.options.id)),this.map_element||(this.map_element=this.wrapper.querySelector(".map-item"))},e.render=function(){this.map=new google.maps.Map(this.map_element,{zoom:this.defaultZoom,center:{lat:this.options.lat,lng:this.options.long},mapTypeId:this.options.view,mapTypeControl:this.options.pro,streetViewControl:this.options.pro,scaleControl:!!this.options.scale}),this.wrapper.GoogleMap=this;var t=new CustomEvent("onTFMapWidgetRender",{detail:{map:this.wrapper,service:"googlemap"}});document.dispatchEvent(t)},e.renderMarkers=function(){var n,s,a=this;0!==this.options.markers.length&&(n=this,s=new google.maps.InfoWindow,this.options.markers.map(function(o,t){var i,e={position:new google.maps.LatLng(o.latitude,o.longitude),map:a.map},e=(""!==a.options.markerImage&&(e.icon=a.options.markerImage),new google.maps.Marker(e));a.markersBounds.extend(e.position),a.options.enable_info_window&&(o.label&&""!==o.label||o.description&&""!==o.description||o.address&&""!==o.address)&&google.maps.event.addListener(e,"click",(i=e,function(){var t=(t=o.label&&""!==o.label?o.label:o.address&&""!==o.address?o.address:"")&&'<strong class="tf-map-marker-container--title">'+t+"</strong>",e=o.description&&""!==o.description?'<div class="tf-map-marker-container--content">'+o.description+"</div>":"";s.setContent('<div class="tf-map-marker-container">'+t+e+"</div>"),s.open(n.map,i)}))}))},e.centerMap=function(){var t,e,o;0!==this.options.markers.length&&("fitbounds"===this.options.zoom_level?this.map.fitBounds(this.markersBounds):(t=this.options.markers[0].latitude,e=this.options.markers[0].longitude,null!==this.options.map_center&&2===(o=this.options.map_center.split(",")).length&&(t=o[0],e=o[1]),o=new google.maps.LatLng(t,e),this.map.panTo(o),this.map.setZoom(this.defaultZoom)))},e.getMap=function(){return this.map},t}(),TF_Google_Maps=function(){function t(){this.init()}return t.prototype.init=function(){var t,i,e;window.IntersectionObserver&&0!==(t=document.querySelectorAll(".nrf-widget.googlemap:not(.no-map):not(.done)")).length&&(i=1,e=new IntersectionObserver(function(t,o){t.forEach(function(t){var e;t.isIntersecting&&(t.target.id=t.target.id+"-"+i,t.target.classList.add("done"),(e=t.target.hasAttribute("data-options")?JSON.parse(t.target.dataset.options):t.target).id=t.target.id,(e=new TF_Google_Map(e)).render(),e.renderMarkers(),e.centerMap(),o.unobserve(t.target),i++)})},{rootMargin:"0px 0px 0px 0px"}),t.forEach(function(t){e.observe(t)}))},t}();document.addEventListener("DOMContentLoaded",function(){new TF_Google_Maps});

