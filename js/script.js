$(function(){
	var regionChanger = {
		init: function(){
			// console.log('init');
			this.ui.init();
		},
		ui: {
			selector: null,
			regions: null,
			regionsByID: {},
			init: function(){
				// console.log('ui.init');
				this.selector = $('#region-selector');
				this.regions = $('.region-tide-data');
				$(this.selector.children('option')).each(function(){
					var regionID = this.value;
					regionChanger.ui.regionsByID[regionID] = $('.region-tide-data-' + regionID);
				});
				this.initEvents();
				this.events.selector.change();
			},
			initEvents: function(){
				// console.log('ui.initEvents');
				this.selector.on('change', regionChanger.ui.events.selector.change);
			},
			events: {
				selector: {
					change: function(event){
						// console.log('ui.events.selectorChange');
						var regionID = regionChanger.ui.selector.val();
						regionChanger.ui.events.regions.show(regionID);
					}
				},
				regions: {
					hideAll: function(){
						// console.log('ui.events.regions.hideAll');
						regionChanger.ui.regions.hide();
					},
					show: function(regionID){
						// console.log('ui.events.regions.show', regionID);
						regionChanger.ui.events.regions.hideAll();
						var regions = regionChanger.ui.regionsByID[regionID];
						$.each(regions, function(k, v){
							$(v).show();
						});
					}
				}
			}
		}
	}
	regionChanger.init();
});