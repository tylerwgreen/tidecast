function initMap(){
	var homeLatLng = {lat: 45.5026, lng: -122.6794};
	var map = new google.maps.Map(
		document.getElementById('map-container'),
		{
			zoom: 6,
			center: homeLatLng,
			disableDefaultUI: true,
			mapTypeId: 'satellite'
		}
	);
	var home = new google.maps.Marker({
		position: homeLatLng,
		map: map,
		title: 'Home'
	});
	zones.init(
		map,
		zonesData
	);
}
var zones = {
	debug: false,
	zones: {},
	map: null,
	colors: {
		default:		'#666666',
		rain:			'#ff8d8d',
		thunder:		'#c385c3',
		snow:			'#85b9c3',
		fog:			'#63c363',
	},
	init: function(map, zonesData){
		this.map = map;
		if(window.debug){
			this.debug = true;
			console.log('DEBUG MODE ON');
		}
		if(this.debug){
			// delay for less build
			setTimeout(function(){
				zones.ui.init(zonesData);
				zones.zonesInit(zonesData);
			}, 250);
		}else{
			zones.ui.init(zonesData);
			zones.zonesInit(zonesData);
		}
	},
	zonesInit: function(zonesData){
		$.each(zonesData, function(zoneID, zoneData){
			zones.zones[zoneID] = new zones._zone(zoneID, zoneData);
		});
	},
	_zone: function(zoneID, zoneData){
		var self = this;
		this.ID = zoneID;
		this.forecast = zoneData.forecast;
		this.properties = zoneData.properties;
		this.coordinatesCentral = zoneData.coordinatesCentral;
		// functions
		this.colors = {
			getZoneColor: function(){
				if(self.forecast.fog){
					return zones.colors.fog;
				}else if(self.forecast.thunder){
					return zones.colors.thunder;
				}else if(self.forecast.snow){
					return zones.colors.snow;
				}else if(self.forecast.rain){
					return zones.colors.rain;
				}else{
					return zones.colors.default;
				}
			},
			getPeriodColor: function(periodID){
				if('undefined' === typeof(self.forecast.periods[periodID])){
					return zones.colors.default;
				}else{
					var period = self.forecast.periods[periodID];
					if(period.fog){
						return zones.colors.fog;
					}else if(period.thunder){
						return zones.colors.thunder;
					}else if(period.snow){
						return zones.colors.snow;
					}else if(period.rain){
						return zones.colors.rain;
					}else{
						return zones.colors.default;
					}
				}
			},
			getZoneColorByWeatherType: function(weatherType){
				if(
						weatherType === 'fog'
					&&	self.forecast[weatherType]
				){
					return zones.colors.fog;
				}else if(
						weatherType === 'thunder'
					&&	self.forecast[weatherType]
				){
					return zones.colors.thunder;
				}else if(
						weatherType === 'snow'
					&&	self.forecast[weatherType]
				){
					return zones.colors.snow;
				}else if(
						weatherType === 'rain'
					&&	self.forecast[weatherType]
				){
					return zones.colors.rain;
				}else{
					return zones.colors.default;
				}
			},
			
		}
		// build polygon
		this.polygon = new google.maps.Polygon({
			paths: zones.convertCoordinates(zoneData.coordinates),
			strokeColor: self.colors.getZoneColor(),
			strokeOpacity: 0.5,
			strokeWeight: 0.5,
			fillColor: self.colors.getZoneColor(),
			fillOpacity: 0.5
		});
		this.setColorByWeatherType = function(weatherType){
			this.polygon.setOptions({
				fillColor: self.colors.getZoneColorByWeatherType(weatherType),
				strokeColor: self.colors.getZoneColorByWeatherType(weatherType)
			});
		}
		this.setColorByZone = function(){
			this.polygon.setOptions({
				fillColor: self.colors.getZoneColor(),
				strokeColor: self.colors.getZoneColor()
			});
		}
		this.setColorByPeriod = function(periodID){
			this.polygon.setOptions({
				fillColor: self.colors.getPeriodColor(periodID),
				strokeColor: self.colors.getPeriodColor(periodID)
			});
		}
		this.setColorByMouseOver = function(){
			this.polygon.setOptions({
				strokeOpacity: 1,
				fillOpacity: 1
			});
		}
		this.setColorByMouseOut = function(){
			this.polygon.setOptions({
				strokeOpacity: .5,
				fillOpacity: .5
			});
		}
		this.polygon.setMap(zones.map);
		// add listeners
		this.polygon.addListener('click', function(event){
			self.events.click(this, event);
		});
		this.polygon.addListener('mouseover', function(event){
			self.events.mouseOver(this, event);
		});
		this.polygon.addListener('mouseout', function(event){
			self.events.mouseOut(this, event);
		});
		// events
		this.events = {
			click: function(polygon, event){
				zones.ui.forecastModal.populate(
					self.ID,
					self.properties,
					self.forecast,
					self.coordinatesCentral
				);
			},
			mouseOver: function(polygon, event){
				zones.ui.zoneData.update(
					self.ID,
					self.forecast,
					self.properties
				);
				self.setColorByMouseOver();
			},
			mouseOut: function(polygon, event){
				zones.ui.zoneData.reset();
				self.setColorByMouseOut();
			}
		}
	},
	ui: {
		init: function(zonesData){
			this.weatherSelector.init();
			this.zoneData.init();
			this.periodData.init();
this.periodSelector.init(zonesData);
// this.slider.init(zonesData);
			this.zones.init();
			this.map.init();
			this.windowScreen.init();
			this.forecastModal.init();
		},
		weatherSelector: {
			weatherSelector: null,
			btns: {
				fog: null,
				thunder: null,
				snow: null,
				rain: null,
			},
			init: function(){
				this.weatherSelector = $('#weather-selector');
				this.btns.fog = $('#weather-type-fog');
				this.btns.thunder = $('#weather-type-thunder');
				this.btns.snow = $('#weather-type-snow');
				this.btns.rain = $('#weather-type-rain');
				$.each(this.btns, function(k, btn){
					btn.on('mouseover', zones.ui.weatherSelector.colorZones);
					btn.on('touchstart', zones.ui.weatherSelector.colorZones);
					btn.on('touchmove', zones.ui.weatherSelector.touchMove);
					btn.on('mouseout', zones.ui.weatherSelector.resetZones);
					btn.on('touchend', zones.ui.weatherSelector.resetZones);
				});
			},
			touchMove: function(event){
				console.log('touchMove');
			},
			colorZones: function(event){
				event.preventDefault();
				event.stopPropagation();
				var btn = $(this);
				btn.addClass('active');
				var weatherType = btn.data('weather-type');
				zones.ui.zones.colorByWeatherType(weatherType);
			},
			resetZones: function(event){
				event.preventDefault();
				event.stopPropagation();
				var btn = $(this);
				btn.removeClass('active');
				zones.ui.zones.reset();
			}
		},
		zoneData: {
			zoneData: null,
			init: function(){
				this.zoneData = $('#zone-data');
				this.zoneID.init();
				this.zoneOverview.init();
				this.zoneUpdated.init();
				this.zoneName.init();
				this.reset();
			},
			getHeight: function(){
				return this.zoneData.height();
			},
			show: function(){
				this.zoneData.show();
			},
			hide: function(){
				this.zoneData.hide();
			},
			update: function(ID, forecast, properties){
				this.zoneID.update(ID);
				this.zoneOverview.update(forecast);
				this.zoneUpdated.update(forecast.updatedReadable);
				this.zoneName.update(properties.name);
				this.show();
			},
			reset: function(){
				this.zoneID.reset();
				this.zoneOverview.reset();
				this.zoneUpdated.reset();
				this.zoneName.reset();
				this.hide();
			},
			zoneID: {
				zoneID: null,
				init: function(){
					this.zoneID = $('#zone-data #zone-id');
				},
				update: function(data){
					this.zoneID.text(data);
				},
				reset: function(){
					this.zoneID.text('');
				}
			},
			zoneOverview: {
				zoneOverview: null,
				fog: null,
				thunder: null,
				rain: null,
				snow: null,
				init: function(){
					this.zoneOverview = $('#zone-overview');
					this.fog = this.zoneOverview.children('#zone-overview-fog');
					this.thunder = this.zoneOverview.children('#zone-overview-thunder');
					this.rain = this.zoneOverview.children('#zone-overview-rain');
					this.snow = this.zoneOverview.children('#zone-overview-snow');
				},
				update: function(forecast){
					if(forecast.fog){
						this.fog.css('background-color', zones.colors.fog);
					}
					if(forecast.thunder){
						this.thunder.css('background-color', zones.colors.thunder);
					}
					if(forecast.rain){
						this.rain.css('background-color', zones.colors.rain);
					}
					if(forecast.snow){
						this.snow.css('background-color', zones.colors.snow);
					}
				},
				reset: function(){
					this.fog.css('background-color', zones.colors.default);
					this.thunder.css('background-color', zones.colors.default);
					this.rain.css('background-color', zones.colors.default);
					this.snow.css('background-color', zones.colors.default);
				}
			},
			zoneUpdated: {
				zoneUpdated: null,
				init: function(){
					this.zoneUpdated = $('#zone-data #zone-updated');
				},
				update: function(data){
					this.zoneUpdated.text(data);
				},
				reset: function(){
					this.zoneUpdated.text('');
				}
			},
			zoneName: {
				zoneName: null,
				init: function(){
					this.zoneName = $('#zone-data #zone-name');
				},
				update: function(data){
					this.zoneName.text(data);
				},
				reset: function(){
					this.zoneName.text('');
				}
			},
		},
		periodData: {
			periodData: null,
			periodNames: null,
			init: function(){
				this.periodData = $('#period-data');
				this.periodNames = $('#period-names');
				this.hide();
			},
			show: function(){
				this.periodData.show();
			},
			hide: function(){
				this.periodData.hide();
			},
			update: function(period){
				this.addPeriodsNames(period);
				this.show();
			},
			reset: function(){
				this.hide();
				this.periodNames.empty();
			},
			addPeriodsNames: function(period){
				$.each(period, function(k, name){
					zones.ui.periodData.periodNames.append($('<li/>', {
						text: name
					}));
				});
			}
		},
		periodSelector: {
			periodSelector: null,
			slider: null,
			handle: null,
			periods: [],
			init: function(zonesData){
				this.periodSelector = $('#period-selector');
				this.slider = $('#period-selector-slider');
				this.handle = $('#period-selector-custom-handle');
				this.parsePeriods(zonesData);
				this.build();
			},
			getHeight: function(){
				return this.slider.height();
			},
			parsePeriods: function(){
				$.each(zonesData, function(zoneID, zone){
					$.each(zone.forecast.periods, function(k, period){
						if(zones.ui.periodSelector.periods.length < zone.forecast.periods.length){
							zones.ui.periodSelector.periods.push([]);
						}
						if(0 > zones.ui.periodSelector.periods[k].indexOf(period.name)){
							zones.ui.periodSelector.periods[k].push(period.name);
						}
					});
				});
			},
			build: function(){
				this.slider.slider({
					min: 1,
					max: zones.ui.periodSelector.periods.length,
					create: function(){
						zones.ui.periodSelector.handle.text(0);
					},
					slide: function(event, ui){
						zones.ui.periodSelector.update(ui.value, ui.value - 1);
					},
					start: function(event, ui){
						zones.ui.periodSelector.update(ui.value, ui.value - 1);
					},
					stop: function(event, ui){
						zones.ui.periodSelector.reset();
					}
				});
			},
			update: function(periodID, periodName){
				zones.ui.periodSelector.reset();
				zones.ui.periodSelector.handle.text(periodName);
				zones.ui.zones.colorByPeriod(periodID);
				zones.ui.periodData.update(zones.ui.periodSelector.periods[periodID]);
			},
			reset: function(){
				zones.ui.zones.reset();
				zones.ui.periodData.reset();
			}
		},
		zones: {
			init: function(){},
			colorByWeatherType: function(weatherType){
				$.each(zones.zones, function(zoneID, zone){
					zone.setColorByWeatherType(weatherType);
				});
			},
			colorByPeriod: function(periodID){
				$.each(zones.zones, function(zoneID, zone){
					zone.setColorByPeriod(periodID);
				});
			},
			reset: function(){
				$.each(zones.zones, function(zoneID, zone){
					zone.setColorByZone();
				});
			}
		},
		windowScreen: {
			windowScreen: null,
			init: function(){
				this.windowScreen = $(window);
				this.resize();
				this.windowScreen.on('resize', this.resize);
			},
			getHeight: function(){
				return this.windowScreen.height();
			},
			resize: function(){
				zones.ui.map.resize(
					zones.ui.zoneData.getHeight(),
					zones.ui.windowScreen.getHeight() - zones.ui.zoneData.getHeight() - zones.ui.periodSelector.getHeight()
				);
			}
		},
		map: {
			map: null,
			init: function(){
				this.map = $('#map-container');
			},
			resize: function(top, height){
				this.map.css('top', top);
				this.map.height(height);
			},
			getHeight: function(){
				return this.map.height();
			},
			setHeight: function(height){
				this.map.height(height);
			}
		},
		forecastModal: {
			hourlyForecastBaseURL: 'https://forecast.weather.gov/MapClick.php?w0=t&w1=td&w2=wc&w3=sfcwind&w3u=1&w4=sky&w5=pop&w6=rh&w7=rain&w8=thunder&w9=snow&w10=fzg&w11=sleet&w12=fog&w13u=0&w16u=1&AheadHour=0&Submit=Submit&FcstType=graphical&site=all&unit=0&dd=&bw=',
			locationMapBaseURL: 'https://www.google.com/maps/search/@',
			modal: null,
			btnClose: null,
			id: null,
			updated: null,
			name: null,
			forecastPeriods: null,
			init: function(){
				this.modal = $('#forecast-modal');
				this.btnClose = $('#forecast-modal-btn-close');
				this.id = $('#forecast-data-id');
				this.updated = $('#forecast-data-updated');
				this.name = $('#forecast-data-name');
				this.forecastPeriods = $('#forecast-periods');
				this.btnClose.on('click', this.reset);
				this.reset();
			},
			hide: function(){
				this.modal.hide();
			},
			show: function(){
				this.modal.show();
			},
			reset: function(){
				zones.ui.forecastModal.hide();
				zones.ui.forecastModal.forecastPeriods.empty();
				zones.ui.zoneData.reset();
			},
			populate: function(ID, properties, forecast, coordinatesCentral){
				this.id.text(ID);
				this.id.attr(
					'href',
					this.hourlyForecastBaseURL + '&textField1=' + coordinatesCentral.x + '&textField2=' + coordinatesCentral.y
				);
				this.updated.text(forecast.updatedReadable);
				this.name.text(properties.name);
				this.name.attr(
					'href',
					this.locationMapBaseURL + coordinatesCentral.x + ',' + coordinatesCentral.y + ',9z'
				);
				this.populateForecastPeriods(forecast.periods);
				this.show();
			},
			populateForecastPeriods(periods){
				$.each(periods, function(k, period){
					var wrap = $('<li/>');
					var name = $('<h3/>', {
						text: period.name
					});
					var weatherTypes = $('<ul/>', {
						id: 'weather-types'
					});
					var fog = $('<li/>', {
						class: period.fog ? 'has-fog' : '',
						text: 'Fog',
					});
					var thunder = $('<li/>', {
						class: period.thunder ? 'has-thunder' : '',
						text: 'Thunder',
					});
					var rain = $('<li/>', {
						class: period.rain ? 'has-rain' : '',
						text: 'Rain',
					});
					var snow = $('<li/>', {
						class: period.snow ? 'has-snow' : '',
						text: 'Snow',
					});
					var forecast = $('<p/>', {
						class: 'forecast',
						text: period.forecast
					});
					weatherTypes.append(fog, thunder, snow, rain);
					wrap.append(name);
					wrap.append(weatherTypes);
					wrap.append(forecast);
					zones.ui.forecastModal.forecastPeriods.append(wrap);
				});
			}
		}
	},
	convertCoordinates: function(coordinates){
		var c = [];
		$.each(coordinates, function(i, v){
			var cc = [];
			$.each(v, function(ii, vv){
				cc.push({
					lat: vv[1],
					lng: vv[0]
				});
			});
			c.push(cc);
		});
		return c;
	}
}