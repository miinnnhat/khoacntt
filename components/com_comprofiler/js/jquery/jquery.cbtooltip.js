(function($) {
	var instances = [];
	var methods = {
		init: function( options ) {
			return this.each( function () {
				var $this = this;
				var cbtooltip = $( $this ).data( 'cbtooltip' );

				if ( cbtooltip ) {
					return; // cbtooltip is already bound; so no need to rebind below
				}

				cbtooltip = {};
				cbtooltip.options = ( typeof options != 'undefined' ? options : {} );
				cbtooltip.defaults = $.fn.cbtooltip.defaults;
				cbtooltip.settings = $.extend( true, {}, cbtooltip.defaults, cbtooltip.options );
				cbtooltip.element = $( $this );

				// Parse the elements data for settings overrides if data use is enabled:
				if ( cbtooltip.settings.useData ) {
					$.each( $.fn.cbtooltip.dataMap, function( key, value ) {
						const dataValue = cbtooltip.element.data( value );

						if ( typeof dataValue != 'undefined' ) {
							if ( key.indexOf( 'classes' ) !== -1 ) {
								cbtooltip.settings[key] += ' ' + dataValue;
							} else {
								cbtooltip.settings[key] = dataValue;
							}
						}
					});
				}

				cbtooltip.element.triggerHandler( 'cbtooltip.init.before', [cbtooltip] );

				// Make sure nested tooltips don't prepare yet; we'll do that on render of their parent:
				if ( cbtooltip.element.parents( '.cbTooltip,[data-hascbtooltip="true"]' ).length ) {
					cbtooltip.settings.init = false;
				}

				if ( ! cbtooltip.settings.init ) {
					return;
				}

				var noWidth = ( ( cbtooltip.settings.width == null ) && ( cbtooltip.defaults.width == null ) );
				var tooltipClone = ( cbtooltip.settings.clone != null ? cbtooltip.settings.clone : true );
				var tooltipModal = ( cbtooltip.settings.modal != null ? cbtooltip.settings.modal : ( cbtooltip.settings.classes ? ( cbtooltip.settings.classes.indexOf( 'qtip-modal' ) !== -1 ) : false ) );
				var tooltipDialog = ( cbtooltip.settings.dialog != null ? cbtooltip.settings.dialog : ( cbtooltip.settings.classes ? ( cbtooltip.settings.classes.indexOf( 'qtip-dialog' ) !== -1 ) : false ) );
				var tooltipMenu = ( cbtooltip.settings.menu != null ? cbtooltip.settings.menu : ( cbtooltip.settings.classes ? ( cbtooltip.settings.classes.indexOf( 'qtip-menu' ) !== -1 ) : false ) );
				var tooltipSimple = ( cbtooltip.settings.simple != null ? cbtooltip.settings.simple : ( cbtooltip.settings.classes ? ( cbtooltip.settings.classes.indexOf( 'qtip-simple' ) !== -1 ) : false ) );
				var tooltipButtonHide = ( cbtooltip.settings.buttonHide != null ? cbtooltip.settings.buttonHide : false );
				var tooltipRestore = null;
				var tooltipTemplate = false;

				if ( cbtooltip.settings.tooltipTarget && ( findTarget( cbtooltip, cbtooltip.settings.tooltipTarget, true ) === 'template' ) ) {
					tooltipClone = false;
					tooltipTemplate = true;
				} else {
					// Prepare the restoration of the element encase we're not cloning and we're not keeping alive the tooltip (basically moving instead of cloning):
					tooltipRestore = ( cbtooltip.settings.tooltipTarget ? ( findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ).length ? ( ( ! tooltipClone ) && ( ! cbtooltip.settings.keepAlive ) ? findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ) : null ) : null ) : null );
				}

				var tooltipRestoreParent = ( tooltipRestore && tooltipRestore.length ? tooltipRestore.parent() : null );

				if ( tooltipModal || tooltipDialog ) {
					tooltipMenu = false;
				} else if ( tooltipMenu ) {
					tooltipModal = false;
					tooltipDialog = false;

					// Menu default width needs to be the width supplied or auto as we want it to conform to its content:
					if ( cbtooltip.settings.width != cbtooltip.element.data( 'width' ) ) {
						cbtooltip.settings.width = null;
					}

					// Add accessibility attribute to signify this is a menu popup:
					cbtooltip.element.attr( 'aria-haspopup', true );
					cbtooltip.element.attr( 'aria-expanded', false );
				}

				// Simply keeping track of what type of tooltip this is:
				cbtooltip.tooltiptype = ( tooltipModal ? 'modal' : ( tooltipDialog ? 'dialog' : ( tooltipMenu ? 'menu' : 'tooltip' ) ) );

				// Create the overlay encase we need to insert it:
				cbtooltip.overlay = $( '<div class="qtip-overlay" />' );

				if ( cbtooltip.settings.overlayClasses ) {
					cbtooltip.overlay.addClass( cbtooltip.settings.overlayClasses );
				}

				// Create a handler function for closing a tooltip on escape:
				cbtooltip.escapeHandler = function( e ) {
					if ( e.which == 27 ) {
						cbtooltip.tooltip.qtip( 'api' ).hide();
					}
				};

				var closeEffect = ( cbtooltip.settings.closeEffect != null ? cbtooltip.settings.closeEffect : ( ! ( tooltipMenu || tooltipModal || tooltipDialog ) ) );

				// Prepare qTip with the settings parsed above:
				cbtooltip.tooltip = cbtooltip.element.qtip({
					id: cbtooltip.settings.id,
					overwrite: true,
					content: {
						text: ( tooltipTemplate ? '' : ( cbtooltip.settings.tooltipTarget ? ( findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ).length ? ( tooltipClone ? findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ).clone( true ) : findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ) ) : ( cbtooltip.settings.tooltip ? cbtooltip.settings.tooltip : null ) ) : ( cbtooltip.settings.tooltip ? cbtooltip.settings.tooltip : null ) ) ),
						title: ( cbtooltip.settings.titleTarget ? ( findTarget( cbtooltip, cbtooltip.settings.titleTarget ).length ? ( tooltipClone ? findTarget( cbtooltip, cbtooltip.settings.titleTarget ).clone( true ) : findTarget( cbtooltip, cbtooltip.settings.titleTarget ) ) : ( cbtooltip.settings.title ? cbtooltip.settings.title : null ) ) : ( cbtooltip.settings.title ? cbtooltip.settings.title : null ) ),
						button: ( ( cbtooltip.settings.openEvent && ( cbtooltip.settings.openEvent.indexOf( 'click' ) !== -1 ) ) || tooltipModal || tooltipDialog ? ( tooltipButtonHide || tooltipMenu ? false : cbtooltip.settings.buttonClose ) : false )
					},
					position: {
						container: ( cbtooltip.settings.container ? ( $( cbtooltip.settings.container ).length ? $( cbtooltip.settings.container ) : cbtooltip.settings.container ) : false ),
						viewport: ( cbtooltip.settings.viewport ? ( $( cbtooltip.settings.viewport ).length ? $( cbtooltip.settings.viewport ) : cbtooltip.settings.viewport ) : $( window ) ),
						my: ( cbtooltip.settings.positionMy ? cbtooltip.settings.positionMy : ( tooltipModal || tooltipDialog ? 'center' : ( tooltipSimple ? 'bottom center' : 'top left' ) ) ),
						at: ( cbtooltip.settings.positionAt ? cbtooltip.settings.positionAt : ( tooltipMenu ? 'bottom left' : ( tooltipModal || tooltipDialog ? 'center' : ( tooltipSimple ? 'top center' : 'bottom right' ) ) ) ),
						target: ( cbtooltip.settings.positionTarget ? ( cbtooltip.settings.positionTarget == 'mouse' ? 'mouse' : ( findTarget( cbtooltip, cbtooltip.settings.positionTarget ).length ? findTarget( cbtooltip, cbtooltip.settings.positionTarget ) : false ) ) : ( tooltipModal || tooltipDialog ? $( window ) : false ) ),
						adjust: {
							x: ( cbtooltip.settings.adjustX != null ? cbtooltip.settings.adjustX : 0 ),
							y: ( cbtooltip.settings.adjustY != null ? cbtooltip.settings.adjustY : ( tooltipMenu ? 5 : 0 ) ),
							scroll: ( cbtooltip.settings.adjustScroll != null ? cbtooltip.settings.adjustScroll : true ),
							resize: ( cbtooltip.settings.adjustResize != null ? cbtooltip.settings.adjustResize : true ),
							method: ( cbtooltip.settings.adjustMethod ? cbtooltip.settings.adjustMethod : 'shift flipinvert' )
						},
						effect: ( cbtooltip.settings.positionEffect != null ? cbtooltip.settings.positionEffect : false )
					},
					show: {
						ready: ( cbtooltip.settings.openReady != null ? cbtooltip.settings.openReady : tooltipDialog ),
						target: ( cbtooltip.settings.openTarget ? ( findTarget( cbtooltip, cbtooltip.settings.openTarget ).length ? findTarget( cbtooltip, cbtooltip.settings.openTarget ) : false ) : false ),
						event: ( cbtooltip.settings.openEvent ? cbtooltip.settings.openEvent : ( tooltipModal || tooltipDialog ? 'click' : 'mouseenter click' ) ),
						solo: ( cbtooltip.settings.openSolo != null ? ( $( cbtooltip.settings.openSolo ).length ? $( cbtooltip.settings.openSolo ) : ( cbtooltip.settings.openSolo == 'document' ? $( document ) : cbtooltip.settings.openSolo ) ) : ( tooltipModal ? $( document ) : false ) ),
						delay: ( cbtooltip.settings.openDelay != null ? cbtooltip.settings.openDelay : ( tooltipMenu || tooltipModal || tooltipDialog ? 30 : 300 ) ),
						modal: {
							on: tooltipModal,
							escape: false,
							blur: false
						},
						effect: ( cbtooltip.settings.openEffect != null ? cbtooltip.settings.openEffect : ( ! ( tooltipMenu || tooltipModal || tooltipDialog ) ) )
					},
					hide: {
						target: ( cbtooltip.settings.closeTarget ? ( findTarget( cbtooltip, cbtooltip.settings.closeTarget ).length ? findTarget( cbtooltip, cbtooltip.settings.closeTarget ) : false ) : false ),
						event: ( cbtooltip.settings.closeEvent ? cbtooltip.settings.closeEvent : ( tooltipModal || tooltipDialog ? 'none' : 'mouseleave unfocus' ) ),
						fixed: ( cbtooltip.settings.closeFixed != null ? cbtooltip.settings.closeFixed : ( tooltipMenu || tooltipModal || tooltipDialog ) ),
						delay: ( cbtooltip.settings.closeDelay != null ? cbtooltip.settings.closeDelay : ( tooltipMenu ? 200 : 0 ) ),
						distance: ( cbtooltip.settings.closeDistance != null ? cbtooltip.settings.closeDistance : false ),
						leave: ( cbtooltip.settings.closeLeave != null ? cbtooltip.settings.closeLeave : 'window' ),
						inactive: ( cbtooltip.settings.closeInactive != null ? cbtooltip.settings.closeInactive : false ),
						effect: closeEffect
					},
					style: {
						width: ( cbtooltip.settings.width != null ? cbtooltip.settings.width : false ),
						height: ( cbtooltip.settings.height != null ? cbtooltip.settings.height : false ),
						tip: {
							corner: ( cbtooltip.settings.tipHide != null ? cbtooltip.settings.tipHide : ( ! ( tooltipMenu || tooltipModal || tooltipDialog ) ) ),
							width: ( cbtooltip.settings.tipWidth != null ? cbtooltip.settings.tipWidth : 6 ),
							height: ( cbtooltip.settings.tipHeight != null ? cbtooltip.settings.tipHeight : 6 ),
							offset: ( cbtooltip.settings.tipOffset != null ? cbtooltip.settings.tipOffset : 0 )
						},
						classes: ( cbtooltip.settings.classes ? cbtooltip.settings.classes : '' ) + ( tooltipSimple ? ' tooltip' : ' popover' ) + ( tooltipMenu ? ' qtip-menu' : '' ) + ( tooltipSimple ? ' qtip-simple' : '' ) + ( tooltipModal ? ' qtip-modal' : '' ) + ( tooltipDialog ? ' qtip-dialog' : '' ) + ( noWidth ? ' qtip-nowidth' : '' ),
						def: false
					},
					events: {
						render: function( event, api ) {
							if ( tooltipTemplate ) {
								api.set( 'content.text', findTarget( cbtooltip, cbtooltip.settings.tooltipTarget ) );
							}

							if ( cbtooltip.element.hasClass( 'cbSelect' ) ) {
								// Detect if the tooltip will overlap the select2 dropdown and reposition it
								if ( cbtooltip.element.siblings( '.select2.select2-container--below.select2-container--open' ).length && ( api.position.at.y === 'bottom' ) && ( api.position.my.y === 'top' ) ) {
									// Reposition the tooltip to be above the select2 element otherwise it'll stack on the dropdown
									api.set( 'position.at', api.position.at.x + ' top' );
									api.set( 'position.my', api.position.my.x + ' bottom' );
									api.set( 'position.adjust.method', 'shift none' );
								} else if ( cbtooltip.element.siblings( '.select2.select2-container--above.select2-container--open' ).length && ( api.position.at.y === 'top' ) && ( api.position.my.y === 'bottom' ) ) {
									// Reposition the tooltip to be below the select2 element otherwise it'll stack on the dropdown
									api.set( 'position.at', api.position.at.x + ' bottom' );
									api.set( 'position.my', api.position.my.x + ' top' );
									api.set( 'position.adjust.method', 'shift none' );
								}
							} else if ( cbtooltip.element.is( 'select:focus' ) && ( api.position.at.y === 'bottom' ) && ( api.position.my.y === 'top' ) ) {
								// Select elements always go down so reposition the tooltip so it doesn't overlap the dropdown options
								api.set( 'position.at', api.position.at.x + ' top' );
								api.set( 'position.my', api.position.my.x + ' bottom' );
								api.set( 'position.adjust.method', 'shift none' );
							}

							// Add custom title and content classes:
							if ( cbtooltip.settings.titleClasses ) {
								$( api.elements.titlebar ).addClass( cbtooltip.settings.titleClasses );
							}

							if ( cbtooltip.settings.contentClasses ) {
								$( api.elements.content ).addClass( cbtooltip.settings.contentClasses );
							}

							// Add Bootstrap 4 classes:
							if ( tooltipSimple ) {
								$( api.elements.titlebar ).addClass( 'hidden' );
								$( api.elements.content ).addClass( 'tooltip-inner' );
							} else {
								$( api.elements.titlebar ).addClass( 'popover-header' );
								$( api.elements.tooltip ).find( '.qtip-close' ).addClass( 'close' ).find( '.ui-icon' ).removeClass( 'ui-icon ui-icon-close' );
								$( api.elements.content ).addClass( 'popover-body' );
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.render', [cbtooltip, event, api] );

							// Prepare the nested tooltips so they'll function:
							$( api.elements.content ).find( '.cbTooltip,[data-hascbtooltip="true"]' ).each( function() {
								$( this ).cbtooltip();
							});

							if ( tooltipMenu ) {
								$( api.elements.content ).on( 'click', function() {
									if ( closeEffect ) {
										api.toggle( false );
									} else {
										setTimeout( function() {
											api.toggle( false );
										}, 100 );
									}
								});
							}

							calculateContentMaxWidth.call( $this, cbtooltip, api );

							if ( api.elements.overlay ) {
								if ( cbtooltip.settings.overlayClasses ) {
									api.elements.overlay.addClass( cbtooltip.settings.overlayClasses );
								}

								// Fix for qtip2 buggy variable "current" usage by unbinding its events:
								$( document ).off( 'keydown.qtip-modal' );
								api.elements.overlay.off( 'click.qtip-modal' );

								api.elements.overlay.on( 'click', function ( e ) {
									api.toggle( false );
								});
							}

							// Bind to custom close handler so we can have custom close buttons in the content:
							$( api.elements.content ).on( 'click', '.cbTooltipClose', function( e ) {
								e.preventDefault();

								api.toggle( false );
							});
						},
						show: function( event, api ) {
							if ( tooltipMenu ) {
								cbtooltip.element.attr( 'aria-expanded', true );
							}

							if ( tooltipModal ) {
								if ( api.options.position.adjust.scroll ) {
									$( 'body' ).addClass( 'cbTooltipModalOpen' );
								}
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.show', [cbtooltip, event, api] );

							if ( cbtooltip.settings.closeClasses ) {
								$( api.elements.target ).removeClass( cbtooltip.settings.closeClasses );
							}

							if ( cbtooltip.settings.openClasses ) {
								$( api.elements.target ).addClass( cbtooltip.settings.openClasses );
							}
						},
						hide: function( event, api ) {
							if ( tooltipMenu ) {
								cbtooltip.element.attr( 'aria-expanded', false );
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.hide', [cbtooltip, event, api] );

							if ( cbtooltip.settings.openClasses ) {
								$( api.elements.target ).removeClass( cbtooltip.settings.openClasses );
							}

							if ( cbtooltip.settings.closeClasses ) {
								$( api.elements.target ).addClass( cbtooltip.settings.closeClasses );
							}

							if ( tooltipRestore && tooltipRestore.length ) {
								tooltipRestoreParent.append( tooltipRestore );
							}
						},
						toggle: function( event, api ) {
							cbtooltip.element.triggerHandler( 'cbtooltip.toggle', [cbtooltip, event, api] );
						},
						visible: function( event, api ) {
							if ( tooltipModal ) {
								$( document ).on( 'keydown', cbtooltip.escapeHandler );

								calculateContentMaxHeight.call( $this, cbtooltip, api );
							} else if ( cbtooltip.settings.overlay ) {
								cbtooltip.overlay.insertBefore( api.elements.tooltip ).css({
									zIndex: ( api.elements.tooltip.css( 'zIndex' ) - 1 ),
									cursor: 'default'
								});
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.visible', [cbtooltip, event, api] );
						},
						hidden: function( event, api ) {
							if ( tooltipModal ) {
								$( document ).off( 'keydown', cbtooltip.escapeHandler );

								if ( api.options.position.adjust.scroll ) {
									$( 'body' ).removeClass( 'cbTooltipModalOpen' );
								}
							}

							if ( ( ! tooltipModal ) && cbtooltip.settings.overlay ) {
								cbtooltip.overlay.remove();
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.hidden', [cbtooltip, event, api] );

							if ( tooltipDialog ) {
								if ( ! cbtooltip.element.data( 'cbtooltip' ) ) {
									// We lost our data; lets clean leftover tooltip elements:
									if ( api.elements.overlay ) {
										api.elements.overlay.remove();
									}

									if ( api.elements.tooltip ) {
										api.elements.tooltip.remove();
									}
								} else {
									cbtooltip.element.cbtooltip( 'destroy' );
									cbtooltip.element.remove();
								}
							} else {
								if ( ! cbtooltip.settings.keepAlive ) {
									if ( ! cbtooltip.element.data( 'cbtooltip' ) ) {
										// We lost our data; lets clean leftover tooltip elements:
										if ( api.elements.overlay ) {
											api.elements.overlay.remove();
										}

										if ( api.elements.tooltip ) {
											api.elements.tooltip.remove();
										}
									} else {
										cbtooltip.options.id = api.get( 'id' );

										cbtooltip.element.cbtooltip( 'destroy' );
										cbtooltip.element.cbtooltip( cbtooltip.options );
									}
								}
							}
						},
						move: function( event, api ) {
							calculateContentMaxWidth.call( $this, cbtooltip, api );

							if ( tooltipModal ) {
								calculateContentMaxHeight.call( $this, cbtooltip, api );
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.move', [cbtooltip, event, api] );
						},
						focus: function( event, api ) {
							if ( api.elements.overlay ) {
								// If an overlay exists make sure it's not sitting on top of the active tooltip:
								var overlayIndex = api.elements.overlay.css( 'zIndex' );
								var tooltipIndex = api.elements.tooltip.css( 'zIndex' );

								if ( overlayIndex > tooltipIndex ) {
									api.elements.overlay.css({
										zIndex: ( tooltipIndex - 1 )
									});
								}
							}

							cbtooltip.element.triggerHandler( 'cbtooltip.focus', [cbtooltip, event, api] );
						},
						blur: function( event, api ) {
							cbtooltip.element.triggerHandler( 'cbtooltip.blur', [cbtooltip, event, api] );
						}
					}
				});

				// Destroy the cbtooltip element:
				if ( tooltipModal || tooltipDialog ) {
					// Only destroy modal or dialog if it has been directly closed:
					cbtooltip.element.on( 'destroy.cbtooltip', function() {
						cbtooltip.element.cbtooltip( 'destroy' );
					});
				} else {
					cbtooltip.element.on( 'remove.cbtooltip destroy.cbtooltip', function() {
						cbtooltip.element.cbtooltip( 'destroy' );
					});
				}

				// Rebind the cbtooltip element to pick up any data attribute modifications:
				cbtooltip.element.on( 'rebind.cbtooltip', function() {
					cbtooltip.element.cbtooltip( 'rebind' );
				});

				// If the cbtooltip element is modified we need to rebuild it to ensure all our bindings are still ok:
				cbtooltip.element.on( 'modified.cbtooltip', function( e, oldId, newId, index ) {
					if ( oldId != newId ) {
						var targets = ['tooltip-target', 'title-target', 'open-target', 'close-target', 'position-target'];

						$.each( targets, function( targetId, target ) {
							var targetAttr = cbtooltip.element.attr( 'data-cbtooltip-' + target );

							if ( typeof targetAttr != 'undefined' ) {
								cbtooltip.element.attr( 'data-cbtooltip-' + target, targetAttr.replace( oldId, newId ) );
							}

							var targetData = cbtooltip.element.data( 'cbtooltip-' + target );

							if ( typeof targetData != 'undefined' ) {
								cbtooltip.element.data( 'cbtooltip-' + target, targetData.replace( oldId, newId ) );
							}
						});

						cbtooltip.element.cbtooltip( 'rebind' );
					}
				});

				// If the cbtooltip is cloned we need to rebind it back:
				cbtooltip.element.on( 'cloned.cbtooltip', function() {
					$( this ).off( '.cbtooltip' );

					var eventNamespace = $( this ).data( 'qtip' )._id;

					$( this ).removeData( 'cbtooltip' );
					$( this ).removeData( 'hasqtip' );
					$( this ).removeData( 'qtip' );
					$( this ).removeAttr( 'data-hasqtip' );
					$( this ).off( '.' + eventNamespace );
					$( this ).cbtooltip( cbtooltip.options );
				});

				cbtooltip.element.triggerHandler( 'cbtooltip.init.after', [cbtooltip] );

				// Bind the cbtooltip to the element so it's reusable and chainable:
				cbtooltip.element.data( 'cbtooltip', cbtooltip );

				// Add this instance to our instance array so we can keep track of our cbtooltip instances:
				instances.push( cbtooltip );
			});
		},
		get: function( option ) {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return null;
			}

			return cbtooltip.tooltip.qtip( 'api' ).get( option );
		},
		set: function( option, value ) {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).set( option, value );

			return this;
		},
		toggle: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).toggle();

			return this;
		},
		show: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).toggle( true );

			return this;
		},
		hide: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).toggle( false );

			return this;
		},
		enable: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).disable( false );

			cbtooltip.element.triggerHandler( 'cbtooltip.enable', [cbtooltip] );

			return this;
		},
		disable: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).disable( true );

			cbtooltip.element.triggerHandler( 'cbtooltip.disable', [cbtooltip] );

			return this;
		},
		reposition: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).reposition();

			return this;
		},
		focus: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).focus();

			return this;
		},
		blur: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).blur();

			return this;
		},
		rebind: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.element.cbtooltip( 'destroy' );
			cbtooltip.element.cbtooltip( cbtooltip.options );

			return this;
		},
		destroy: function() {
			var cbtooltip = $( this ).data( 'cbtooltip' );

			if ( ! cbtooltip ) {
				return this;
			}

			cbtooltip.tooltip.qtip( 'api' ).destroy( true );
			cbtooltip.element.off( '.cbtooltip' );

			$.each( instances, function( i, instance ) {
				if ( instance.element == cbtooltip.element ) {
					instances.splice( i, 1 );

					return false;
				}

				return true;
			});

			cbtooltip.element.removeData( 'cbtooltip' );
			cbtooltip.element.triggerHandler( 'cbtooltip.destroyed', [cbtooltip] );

			return this;
		},
		instances: function() {
			return instances;
		}
	};

	function findTarget( cbtooltip, target, targetTypeOnly ) {
		if ( ! target ) {
			if ( targetTypeOnly ) {
				return 'text';
			}

			return cbtooltip.element;
		}

		if ( ( typeof target == 'object' ) && target.jquery ) {
			if ( targetTypeOnly ) {
				return 'element';
			}

			return target;
		} else if ( typeof target != 'string' ) {
			if ( targetTypeOnly ) {
				return 'text';
			}

			return cbtooltip.element;
		}

		if ( ( target.lastIndexOf( '~ ', 0 ) === 0 ) || ( target.lastIndexOf( '+ ', 0 ) === 0 ) || ( target.lastIndexOf( '> ', 0 ) === 0 ) || ( target.lastIndexOf( ':parent ', 0 ) === 0 ) || ( target.lastIndexOf( ' ', 0 ) === 0 ) ) {
			let relativeTarget = cbtooltip.element.find( target.trim() );

			if ( relativeTarget && relativeTarget.is( 'template' ) ) {
				if ( targetTypeOnly ) {
					return 'template';
				}

				relativeTarget = $( relativeTarget.html() );
			}

			if ( targetTypeOnly ) {
				return 'element';
			}

			return relativeTarget;
		}

		let foundTarget = $( target );

		if ( foundTarget && foundTarget.is( 'template' ) ) {
			if ( targetTypeOnly ) {
				return 'template';
			}

			foundTarget = $( foundTarget.html() );
		}

		if ( targetTypeOnly ) {
			return 'element';
		}

		return foundTarget;
	}

	function calculateContentMaxHeight( cbtooltip, api ) {
		if ( api.elements.tooltip ) {
			api.elements.content.css( 'height', '' );
			api.elements.content.css( 'max-height', '' );

			var tipHeight = api.elements.tooltip.height();

			if ( tipHeight ) {
				if ( api.elements.titlebar ) {
					api.elements.titlebar.css( 'height', '' );
					api.elements.titlebar.css( 'max-height', '' );

					var titleHeight = api.elements.titlebar.outerHeight( true );

					if ( titleHeight ) {
						api.elements.titlebar.css( 'height', '100%' );
						api.elements.titlebar.css( 'max-height', titleHeight );

						if ( api.elements.content ) {
							api.elements.content.css( 'height', '100%' );
							api.elements.content.css( 'max-height', ( tipHeight - titleHeight ) );
						}
					}
				} else if ( api.elements.content ) {
					api.elements.content.css( 'height', '100%' );
					api.elements.content.css( 'max-height', tipHeight );
				}
			}
		}
	}

	function calculateContentMaxWidth( cbtooltip, api ) {
		// Checks to see if the tooltip is too wide for the window and shrinks it as needed (auto makes tooltips mobile friendly):
		if ( api.elements.tooltip && ( ( ! cbtooltip.settings.width ) || ( typeof cbtooltip.settings.width === 'number' ) ) ) {
			var maxWidth = ( $( window ).width() - 15 );
			var tipWidth = api.elements.tooltip.width();

			if ( tipWidth > maxWidth ) {
				api.elements.tooltip.css( 'max-width', maxWidth );
			} else if ( ( tipWidth + 15 ) < maxWidth ) {
				api.elements.tooltip.css( 'max-width', '' );
			}
		}
	}

	$.cbconfirm = function( message, options ) {
		var deferred = $.Deferred();
		var confirm = $( '<div />' ).appendTo( 'body' );

		confirm.data( 'cbtooltip-tooltip', message );
		confirm.data( 'cbtooltip-dialog', true );
		confirm.data( 'cbtooltip-button-hide', true );
		confirm.data( 'cbtooltip-width', false );
		confirm.data( 'cbtooltip-overlay', true );

		confirm.on( 'cbtooltip.render', function( e, cbtooltip, event, api ) {
			$( api.elements.content ).on( 'click', '.cbTooltipButtonYes', function( e ) {
				deferred.resolve( 'yes' );
			});
		}).on( 'cbtooltip.hidden', function( e, cbtooltip, event, api ) {
			if ( deferred.state() != 'resolved' ) {
				deferred.reject( 'no' );
			}
		});

		var tooltip = methods.init.apply( confirm, [options] );
		var cbtooltip = tooltip.data( 'cbtooltip' );

		if ( cbtooltip && ( cbtooltip.settings.buttonYes || cbtooltip.settings.buttonNo ) ) {
			message		+=	'<div class="text-right mt-2 cbTooltipButtons">'
						+		( cbtooltip.settings.buttonYes ? '<button class="btn btn-sm btn-primary cbTooltipButtonYes cbTooltipClose">' + cbtooltip.settings.buttonYes + '</button>' : '' )
						+		( cbtooltip.settings.buttonNo ? ' <button class="btn btn-sm btn-secondary cbTooltipButtonNo cbTooltipClose">' + cbtooltip.settings.buttonNo + '</button>' : '' )
						+	'</div>';

			tooltip.cbtooltip( 'set', 'content.text', message );
		}

		return deferred.promise();
	};

	$.cbprompt = function( message, options ) {
		var deferred = $.Deferred();
		var prompt = $( '<div />' ).appendTo( 'body' );

		prompt.data( 'cbtooltip-tooltip', message );
		prompt.data( 'cbtooltip-dialog', true );
		prompt.data( 'cbtooltip-button-hide', true );
		prompt.data( 'cbtooltip-width', false );

		prompt.on( 'cbtooltip.hidden', function( e, cbtooltip, event, api ) {
			deferred.resolve( 'yes' );
		});

		var tooltip = methods.init.apply( prompt, [options] );
		var cbtooltip = tooltip.data( 'cbtooltip' );

		if ( cbtooltip && cbtooltip.settings.buttonYes ) {
			message		+=	'<div class="text-right mt-2 cbTooltipButtons">'
						+		'<button class="btn btn-sm btn-primary cbTooltipButtonYes cbTooltipClose">' + cbtooltip.settings.buttonYes + '</button>'
						+	'</div>';

			tooltip.cbtooltip( 'set', 'content.text', message );
		}

		return deferred.promise();
	};

	$.cbdialog = function( message, options ) {
		var dialog = $( '<div />' ).appendTo( 'body' );

		dialog.data( 'cbtooltip-tooltip', message );
		dialog.data( 'cbtooltip-dialog', true );
		dialog.data( 'cbtooltip-width', false );

		return methods.init.apply( dialog, [options] );
	};

	$.cbmodal = function( message, options ) {
		var modal = $( '<div />' ).appendTo( 'body' );

		modal.data( 'cbtooltip-tooltip', message );
		modal.data( 'cbtooltip-modal', true );
		modal.data( 'cbtooltip-dialog', true );
		modal.data( 'cbtooltip-width', false );

		return methods.init.apply( modal, [options] );
	};

	$.fn.cbtooltip = function( options ) {
		if ( methods[options] ) {
			return methods[ options ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
		} else if ( ( typeof options === 'object' ) || ( ! options ) ) {
			return methods.init.apply( this, arguments );
		}

		return this;
	};

	$.fn.cbtooltip.dataMap = {
		id: 'cbtooltip-id',
		tooltip: 'cbtooltip-tooltip',
		tooltipTarget: 'cbtooltip-tooltip-target',
		title: 'cbtooltip-title',
		titleTarget: 'cbtooltip-title-target',
		openReady: 'cbtooltip-open-ready',
		openEvent: 'cbtooltip-open-event',
		openTarget: 'cbtooltip-open-target',
		openClasses: 'cbtooltip-open-classes',
		openSolo: 'cbtooltip-open-solo',
		openDelay: 'cbtooltip-open-delay',
		openEffect: 'cbtooltip-open-effect',
		closeEvent: 'cbtooltip-close-event',
		closeTarget: 'cbtooltip-close-target',
		closeClasses: 'cbtooltip-close-classes',
		closeFixed: 'cbtooltip-close-fixed',
		closeDelay: 'cbtooltip-close-delay',
		closeDistance: 'cbtooltip-close-distance',
		closeLeave: 'cbtooltip-close-leave',
		closeInactive: 'cbtooltip-close-inactive',
		closeEffect: 'cbtooltip-close-effect',
		buttonHide: 'cbtooltip-button-hide',
		buttonClose: 'cbtooltip-button-close',
		buttonYes: 'cbtooltip-button-yes',
		buttonNo: 'cbtooltip-button-no',
		width: 'cbtooltip-width',
		height: 'cbtooltip-height',
		modal: 'cbtooltip-modal',
		dialog: 'cbtooltip-dialog',
		menu: 'cbtooltip-menu',
		simple: 'cbtooltip-simple',
		clone: 'cbtooltip-clone',
		classes: 'cbtooltip-classes',
		titleClasses: 'cbtooltip-title-classes',
		contentClasses: 'cbtooltip-content-classes',
		overlayClasses: 'cbtooltip-overlay-classes',
		container: 'cbtooltip-container',
		viewport: 'cbtooltip-viewport',
		positionMy: 'cbtooltip-position-my',
		positionAt: 'cbtooltip-position-at',
		positionTarget: 'cbtooltip-position-target',
		positionEffect: 'cbtooltip-position-effect',
		adjustX: 'cbtooltip-adjust-x',
		adjustY: 'cbtooltip-adjust-y',
		adjustScroll: 'cbtooltip-adjust-scroll',
		adjustResize: 'cbtooltip-adjust-resize',
		adjustMethod: 'cbtooltip-adjust-method',
		tipHide: 'cbtooltip-tip-hide',
		tipWidth: 'cbtooltip-tip-width',
		tipHeight: 'cbtooltip-tip-height',
		tipOffset: 'cbtooltip-tip-offset',
		keepAlive: 'cbtooltip-keep-alive',
		overlay: 'cbtooltip-overlay'
	};

	$.fn.cbtooltip.defaults = {
		init: true,
		useData: true,
		id: null,
		tooltip: null,
		tooltipTarget: null,
		title: null,
		titleTarget: null,
		openReady: null,
		openEvent: null,
		openTarget: null,
		openClasses: null,
		openSolo: null,
		openDelay: null,
		openEffect: null,
		closeEvent: null,
		closeTarget: null,
		closeClasses: null,
		closeFixed: null,
		closeDelay: null,
		closeDistance: null,
		closeLeave: null,
		closeInactive: null,
		closeEffect: null,
		buttonHide: null,
		buttonClose: null,
		buttonYes: null,
		buttonNo: null,
		width: null,
		height: null,
		modal: null,
		dialog: null,
		menu: null,
		simple: null,
		clone: null,
		classes: null,
		titleClasses: null,
		contentClasses: null,
		overlayClasses: null,
		container: null,
		viewport: null,
		positionMy: null,
		positionAt: null,
		positionTarget: null,
		positionEffect: null,
		adjustX: null,
		adjustY: null,
		adjustScroll: null,
		adjustResize: null,
		adjustMethod: null,
		tipHide: null,
		tipWidth: null,
		tipHeight: null,
		tipOffset: null,
		keepAlive: null,
		overlay: null
	};
})(jQuery);