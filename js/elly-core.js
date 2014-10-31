// TODO: ajaxStack is array of pending ajax requests, make function, which send next ajaxRequest when previous request ends if ajaxStack is not empty.

function EllyModel(list, identity_name) {

	this.list = list;
	this.identity_name = identity_name;
	this.indexes = {};

		// получить модель по уникальному ключу
	this.get = function(code_element) {
		return this.list[this.indexes[code_element]];
	}

		// построение индекса моделей
	this.indexlist = function() {
		var self = this;
		var temp_list = [];

		self.list.forEach(function(element, index){
			temp_list.push(element);
		});
		self.list = temp_list;
		self.list.forEach(function(element, index){
			self.indexes[element[identity_name]] = index;
		});
	}

		// проверяем, есть ли уже модель в списке с таким уникальным ключом
	this.exist = function(codeid) {
		return ( this.indexes[codeid]!==undefined ) ? true : false ;
	}

		// функция добавляет модель, или массив моделей в начало списка
	this.unshift = function(element) {
		var self = this;

		if ( Object.prototype.toString.call(element) == '[object Array]' ) {

			$.each(element, function(index, model) {
				if ( self.exist(element[self.identity_name]) ) {
					self.list[self.indexes[element[self.identity_name]]] = element;
				} else {
					self.list.unshift(element);
				}
			});
			this.indexlist();

		} else {

			if ( this.exist(element[this.identity_name]) ) {
				this.list[this.indexes[element[this.identity_name]]] = element;
			} else {
				this.list.unshift(element);
				this.indexlist();
			}

		}
	}

		// функция добавляет модель, или массив моделей в конец списка
	this.push = function(element) {
		var self = this;

			// если element - массив, значит это массив моделей, в foreach вызываем функцию this.push для каждой модели
		if ( Object.prototype.toString.call(element) == '[object Array]' ) {

			$.each(element, function(index, model) {
				self.push(model);
			});

		} else {

			// если element - объект, то проверяем есть ли уже такая модель в индексе, если нет, то добавляем, если есть, то заменяем на новую
			if ( this.exist(element[this.identity_name]) ) {
				this.list[this.indexes[element[this.identity_name]]] = element;
			} else {
				this.list.push(element);
				this.indexes[element[this.identity_name]] = this.list.length-1;
			}

		}
	}

		// функция очищает список моделей
	this.clear = function(element) {
		this.indexes = {};
		this.list = [];
	}

		// функция удаляет модель из списка
	this.delete = function(codeid) {
		if ( this.exist(codeid) ) {
			delete this.list[this.indexes[codeid]];
			delete this.indexes[codeid];
			this.indexlist();
		}
	}

		// при инициализации объекта строим индекс
	this.indexlist();
}

var EllyCore = ({

	ajaxActive: false,
	ajaxStack:[],
	ajaxLoaderVisible:false,
	filesStack:[],
	renderedTemplates:{},
	filesTotalSize:0,
	filesLoadedSize:0,
	filesCurrentLoadingSize:0,
	profilerTime:0,

	ajax: function(options){
		options.origSuccess = options.success;
		options.origError = options.error;
		var options = $.extend({}, EllyCore.defaults.ajax, options);
		options.success = this.defaults.ajax.success;
		options.error = this.defaults.ajax.error;
		$.ajax(options);
	},

	ajaxRequest: function(options){
		options.element.load(options.url);
	},

	ajaxForm: function(options){
		element = options.element;
		options.element = null;
		options.origSuccess = options.success;
		options.origError = options.error;
		var options = $.extend({}, EllyCore.defaults.ajaxForm, options);
		options.success = this.defaults.ajaxForm.success;
		options.error = this.defaults.ajaxForm.error;
		element.ajaxForm(options);
	},

	showAjaxForm: function(options){

		fillFormParams = {
			url: ( options.url ) ? options.url : null,
			data: ( options.data ) ? options.data : null,
			element: options.element,
		}

		modalParams = {
			element: options.element,
			title: ( options.title ) ? options.title : null,
			callback: ( options.callback ) ? options.callback : null,
			width: ( options.width ) ? options.width : null,
		}

		this.fillForm(fillFormParams);
		this.modal(modalParams);

	},

	/* Поместить круглишок в блок
	EllyCore.ajaxLoader({
		element: $('ID_блока'),
		position: 7 8 9 4 5 6 1 2 3,
		size: 1 2 3 4,
	});
	*/
	ajaxLoader: function(options){
		var options = $.extend({}, EllyCore.defaults.ajaxLoader, options);

		if ( options.show==0 ) {
			$('.elly-loader').remove();
			this.ajaxLoaderVisible = !this.ajaxLoaderVisible;
		} else {
			var $loader = '<div class="elly-loader"><img style="margin: 0 auto; display: block;" src="img/elly-ajax-loading-' + options.size + '.gif" /></div>';
			options.element.append($loader);
			this.ajaxLoaderVisible = !this.ajaxLoaderVisible;
		}
	},

	modal: function(options){
		var options = $.extend({}, EllyCore.defaults.modal, options);

		if ( !options.container ) {
			options.container = $('#elly-modal-container');
		}

		$modalBody = options.container.find('.modal-body');
		$modalDialog = options.container.find('.modal-dialog');

		if ( options.width ) {
			$modalDialog.css('width', options.width);
		}

		if ( options.title ) {
			options.container.find('.modal-title').html( options.title );
		}

		if ( options.url ) {

			var data = $.extend({}, options.data);
			$modalBody.html('<div id="ellyAjaxLoader"></div>');
			options.container.modal();
			var currentRequest = $.ajax({
				url: options.url,
				data: data,
				type: 'POST',
				success: function(data) {
					$modalBody.html(data);
					if ( options.callback ) {
						options.callback();
					}
				},
			});
			options.container.on('hidden.bs.modal', function (e) {
				$(this).off('hidden.bs.modal');
				$modalBody.html('');
				if ( options.width ) {
					$modalDialog.css('width', 600);
				}
				currentRequest.abort();
			});

		} else if ( options.element ) {

			var $originalContainer = options.element.parent();
			options.element.appendTo($modalBody);

			if ( options.callback ) {
				options.container.on('shown.bs.modal', function (e) {
					$(this).off('shown.bs.modal');
					options.callback();
				});
			}

			options.container.on('hidden.bs.modal', function (e) {
				$(this).off('hidden.bs.modal');
				$modalBody.children().appendTo($originalContainer);
				$modalBody.html('');

				if ( options.width ) {
					$modalDialog.css('width', 600);
				}
			});

			options.container.modal();

		} else {
			alert('не задан обязательный параметр `element` или `url` для функции EllyCore.modal()');
		}

	},

/*

	modal: function(options){
		var options = $.extend({}, EllyCore.defaults.modal, options);

		if ( !options.container ) {
			options.container = $('#elly-modal-container');
		}
		var $modalBody = options.container.find('.modal-body');

		if ( options.title ) {
			options.container.find('.modal-title').html( options.title );
		}

		if ( options.url ) {

			var data = $.extend({}, options.data);
			//$modalBody.html('<div id="ellyAjaxLoader"></div>');
			$('body').css('overflow', 'hidden');
			options.container.modal();

			var currentRequest = $.ajax({
				url: options.url,
				data: data,
				type: 'POST',
				success: function(data) {
					$modalBody.html(data);
					if ( options.callback ) {
						options.callback();
					}
				},
			});

			options.container.on('hidden.bs.modal', function (e) {
				$(this).off('hidden.bs.modal');
				$modalBody.html('');
				currentRequest.abort();
				$('body').css('overflow', 'auto');
			});

		} else if ( options.element ) {

			var $originalContainer = options.element.parent();
			options.element.appendTo($modalBody);

			if ( options.callback ) {
				options.container.on('shown.bs.modal', function (e) {
					$(this).off('shown.bs.modal');
					options.callback();
				});
			}

			$('body').css('overflow', 'hidden');
			options.container.modal();

			options.container.on('hidden.bs.modal', function (e) {
				$(this).off('hidden.bs.modal');
				$modalBody.children().appendTo($originalContainer);
				$modalBody.html('');
				$('body').css('overflow', 'auto');
			});

		} else {
			alert('не задан обязательный параметр `element` или `url` для функции EllyCore.modal()');
		}

	},
*/
/*
EllyCore.fillForm({
	element: $('ID_ФОРМЫ'),
	url: EllyCode.url('Модуль', 'Экшен'),
	data: {},
});
*/
	fillForm: function(options){
		$form = (options.element.is('form') || options.element.is('fieldset')) ? options.element : options.element.find('form');
		$form.resetForm();
		if ( options.url ) {
			$form.prop('action', options.url);
		}

		if ( options.data ) {
			$.each(options.data, function(index, value) {
				$form_element = $form.find('[name="' + index + '"]');
				form_element_prop = $form_element.prop('type');
				if ( form_element_prop=='file' ) return;

				isDateField = form_element_prop=='datetime' || form_element_prop=='date' || form_element_prop=='time' || $form_element.hasClass('datepicker');
				isCheckbox = form_element_prop=='checkbox' || form_element_prop=='radio';

				if ( isDateField ) {

					$form_element.val(moment(new Date(value * 1000)).format('DD.MM.YYYY'));
				} else if ( isCheckbox ) {
					$form_element.prop('checked', value);
				} else {
					$form_element.val(value);
				}
			});
		}

		if ( options.callback ) {
			options.callback();
		}
	},

	template: function(options){
		var options = $.extend({}, EllyCore.defaults.template, options);

		if ( !this.renderedTemplates[options.source.attr('id')] ) {
			var source   = options.source.html();
			var template = Handlebars.compile(source);
			this.renderedTemplates[options.source.attr('id')] = template;
		} else {
			var template = this.renderedTemplates[options.source.attr('id')];
		}

		var context  = options.data;
		var html     = template(context);

			// Если не задан параметр element, то просто возращаем html
		if ( !options.element ) {

			options.callback();
			return html;

		} else if ( options.method=='replace' ) {

			options.element.replaceWith(html);

		} else if ( options.method=='append' ) {

			options.element.append(html);

		} else if ( options.method=='prepend' ) {

			options.element.prepend(html);

		} else if ( options.method=='replaceContent' ) {

			options.element.html(html);

		}

		options.callback();
	},

	confirm: function(label) {
		return confirm(label);
	},
	error: function(options) {
		alert(options.message);
	},

	alert: function(options) {
		if ( !humane.info ) {
			humane.info = humane.spawn({ addnCls: 'humane-jackedup-success'});
		}

		humane.info(options);
	},

	url: function(controller, action, params) {

		if ( params ) {
			var tmp_params = '';
			$.each(params, function(index, val) {
				tmp_params += '&' + index + '=' + val;
			});
			params = tmp_params;
		} else {
			params = '';
		}

		return '?' + controller + '@' + action + params;
	},

	profiler:function(label){
		if ( this.profilerTime==0 ) {
			this.profilerTime = new Date();
		} else {
			console.log('(' + label + ') time: ' + (new Date() - this.profilerTime) + " milliseconds");
			this.profilerTime = new Date();
		}
	},

/*
EllyCore.keyFilter({
	element: $('ID_элемента_фильтр'),
	type: 'nums',
});
*/
	keyFilter: function(options){
		if ( options.type=='nums' ) {
				// Только цифры в поле Цена
			options.element.keydown(function(event) {
				// Разрешаем нажатие клавиш backspace, del, tab, enter и esc
				if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 13 || event.keyCode == 27 ||
					 // Разрешаем выделение: Ctrl+A
					(event.keyCode == 65 && event.ctrlKey === true) ||
					 // Разрешаем клавиши навигации: home, end, left, right
					(event.keyCode >= 35 && event.keyCode <= 39) ||
					 // Разрешаем символ точки и минус
					(event.keyCode == 173 || event.keyCode == 190)
				)
					 {
						 return;
				}
				else {
					// Запрещаем всё, кроме клавиш цифр на основной клавиатуре, а также Num-клавиатуре
					if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
						event.preventDefault();
					}
				}
			});
		} else {
			alert('Wrong type passed in EllyCore.keyFilter "' + options.type + '" for "' + options.element + '" element!');
		}
	},

	fixedSidebar: function(options){
		if ( typeof(options.topOffset)=='undefined' || options.topOffset==null ) {
			options.topOffset = 0;
		}
		if ( typeof(options.bottomOffset)=='undefined' || options.bottomOffset==null ) {
			options.bottomOffset = 0;
		}

		$window = $(window);

		$window.resize(function() {
			if ($window.scrollTop() > options.topOffset) {
				$(options.element).css({
					'top': 0,
					'height': $window.height() - options.bottomOffset,
					'position': 'fixed'
				});
			} else {
				$(options.element).css({
					'top': options.topOffset + 'px',
					'height': $window.height() - options.topOffset - options.bottomOffset + window.pageYOffset,
					'position': 'absolute'
				});
			}
		});
		$window.scroll(function() {
			if ($window.scrollTop() > options.topOffset) {
				$(options.element).css({
					'top': 0,
					'height': $window.height() - options.bottomOffset,
					'position': 'fixed'
				});
			} else {
				$(options.element).css({
					'top': options.topOffset + 'px',
					'height': $window.height() - options.topOffset - options.bottomOffset + window.pageYOffset,
					'position': 'absolute'
				});
			}
		});
		$window.resize();
	},


		/* Фильтр и сортировка
EllyCore.filter({
	element: $('ID_элемента_фильтр'),
	filter_elements: ['codeid', 'name'],
});
		*/
	filter: function(options){
		if (options.element==null) {
			EllyCore.error({
				message: 'Ошибка JS: Для функции EllyCore.filter не задан обязательный параметр "element"'
			});
		}
		if (options.filter_elements==null) {
			EllyCore.error({
				message: 'Ошибка JS: Для функции EllyCore.filter не задан обязательный параметр "filter_elements"'
			});
		}

		window.client_filter_order = new List(options.element.attr('id'), {valueNames: options.filter_elements, page: 10000});

		options.element.find('.sort').click(function(){
			options.element.find('.sort').removeClass('btn-success');
			$(this).addClass('btn-success');
		});

		$('#search').keyup(function(event){
			if ( event.keyCode==27 ) {
				$('#search_clear').click();
			}
			if ( $(this).val()!='' ) {
				$('#search_clear').css('display', 'block');
			} else {
				$('#search_clear').css('display', 'none');
			}
		});

		$('#search_clear').click(function(){
			$('#search').val('');
			$('#search_clear').css('display', 'none');
			window.client_filter_order.search('');
		});
	},


	filter: function(options){
		if (options.element==null) {
			alert('Для функции EllyCore.filter не хватает свойства element');
			return;
		}
		if (options.input_element==null) {
			alert('Для функции EllyCore.filter не хватает свойства input_element');
			return;
		}
		if (options.filter_elements==null) {
			alert('Для функции EllyCore.filter не хватает свойства filter_elements');
			return;
		}

		window.ellycore_list = new List(options.element.attr('id'), {valueNames: options.filter_elements, page: 10000});

			// элементы для сортировки (пока не реализованно)
		if ( options.order_elements ) {
			options.element.find('.sort').click(function() {
				options.element.find('.sort').removeClass('btn-success');
				$(this).addClass('btn-success');
			});
		}

		options.input_element.keyup(function() {
			if ( $(this).val()!=='' ) {
				options.input_element.next('.search_clear').css('display', 'block');
			} else {
				options.input_element.next('.search_clear').css('display', 'none');
			}
		});

		options.input_element.next('.search_clear').click(function() {
			options.input_element.val('');
			$(this).css('display', 'none');
			window.ellycore_list.search('');
		});
	},


	defaults:{
		'ajax':{
			type:'GET',
			dataType:'json',
			success:function(response) {
				EllyCore.ajaxActive = false;
				$('#elly-ajax-loading').css('display', 'none');
				$(document).unbind('mousemove');

				if ( response.data && response.data.debug ) {
					$('#tracy-debug-panel-Tracy-Debugger-ajax .tracy-inner').append(response.data.debug);
				}

				if (response.result==1) {
					if (typeof(this.origSuccess)!='undefined') {
						this.origSuccess(response.data);
					}
				} else {
					if(typeof(response.message)=='undefined') {
						alert('Во время запроса произошла ошибка');
					} else {
						alert(response.message);
					}
				}
			},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				EllyCore.ajaxActive = false;
				$('#elly-ajax-loading').css('display', 'none');
				$(document).unbind('mousemove');
				if (typeof(this.origError)!='undefined') {
					this.origError();
				} else {
					alert('Ошибка соединения с сервером.');
				}
			},
			beforeSend:function(qXHR, settings){
				EllyCore.ajaxActive = true;
					// bind mousemove and show ajax loading if event object proveded
				$('#elly-ajax-loading').css('display', 'block');
				if ( typeof(this.event)!='undefined' ) {
					$('#elly-ajax-loading').css({
						position: 'absolute',
						left: this.event.pageX+10,
						top: this.event.pageY+16,
					});
					$(document).bind('mousemove', function(e){
						$('#elly-ajax-loading').css({
							left: e.pageX+10,
							top: e.pageY+16,
						});
					});
				} else {
					$('#elly-ajax-loading').css({
						position: 'fixed',
						left: $(window).width()/2+10,
						top: $(window).height()/2+16,
					});
				}
			},
		},

		'ajaxForm': {
			success:function(response) {
				EllyCore.ajaxActive = false;
				$('#elly-ajax-loading').css('display', 'none');
				$(document).unbind('mousemove');

				if ( response.data && response.data.debug ) {
					$('#tracy-debug-panel-Tracy-Debugger-ajax .tracy-inner').append(response.data.debug);
				}

				if (response.result==1) {
					if (typeof(this.origSuccess)!='undefined') {
						this.origSuccess(response.data);
					}
				} else {
					if(typeof(response.message)=='undefined') {
						alert('Во время запроса произошла ошибка');
					} else {
						alert(response.message);
					}
				}
			},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				EllyCore.ajaxActive = false;
				$('#elly-ajax-loading').css('display', 'none');
				$(document).unbind('mousemove');
				if (typeof(this.origError)!='undefined') {
					this.origError();
				} else {
					alert('Ошибка соединения с сервером.');
				}
			},
			beforeSend:function(qXHR, settings){
				if ( EllyCore.ajaxActive ) {
					return false;
				}
				EllyCore.ajaxActive = true;
					// bind mousemove and show ajax loading if event object proveded
				$('#elly-ajax-loading').css('display', 'block');
				if ( typeof(this.event)!='undefined' ) {
					$('#elly-ajax-loading').css({
						position: 'absolute',
						left: this.event.pageX+10,
						top: this.event.pageY+16,
					});
					$(document).bind('mousemove', function(e){
						$('#elly-ajax-loading').css({
							left: e.pageX+10,
							top: e.pageY+16,
						});
					});
				} else {
					$('#elly-ajax-loading').css({
						position: 'fixed',
						left: $(window).width()/2+10,
						top: $(window).height()/2+16,
					});
				}
			},
		},

		'showAjaxForm':{
			element:null,
			data:null,
			url:null,
			title:null,
			callback:function(data){},
		},

		'ajaxLoader':{
			element: null,
			position: 5,
			size: 3,
		},

		'modal':{
			element:null,
			container:null,
			data:null,
			url:null,
			title:null,
			callback:function(){},
		},

		'template':{
			element:null,
			source:null,
			data:null,
			method:'replaceContent',
			callback:function(){},
		},

		'multiselect':{
			element:null,
			checkAllText:'Выбрать все',
			uncheckAllText:'Снять все',
			noneSelectedText:'Выберите опции',
			selectedText:'Выбрано # из #',
			show:["blind", 200],
			hide:"explode",
			callback:function(event, ui){},
		},

	}
});

// Хелперы для js шаблонизатора для вывода даты и ссылки

/*
	{{link module=user action=view id=codeid}}
*/
Handlebars.registerHelper('link', function(params) {
	var module = params.hash.module || 'index';
	var action = params.hash.action || 'index';

	str_params = '';
	if ( params.hash!={} ) {
		$.each(params.hash, function(index, value){
			if ( index=='module' || index=='action' ) return;
			str_params += '&'+index+'='+value;
		});
	}
	return '?module='+module+'&action='+action+str_params;
});

Handlebars.registerHelper('dateFormat', function(context, block) {
	var f = block.hash.format || "LL";
	if ( context===false || context===undefined ) return '';
	return moment(new Date(context * 1000)).format(f);
});

Handlebars.registerHelper("debug", function(optionalValue) {
	console.log("Current Context");
	console.log("====================");
	console.log(this);

	if (optionalValue) {
		console.log("Value");
		console.log("====================");
		console.log(optionalValue);
	}
});

Handlebars.registerHelper('render', function(partialId, options) {
	var selector = 'script[type="text/template"]#' + partialId,
		source = $(selector).html(),
		html = Handlebars.compile(source)(options.hash);

	return new Handlebars.SafeString(html);
});

var defaultAvatarPath = {
	main: '/img/dummy/user_photo_main.gif',
	square: '/img/dummy/user_photo_square.gif',
}

Handlebars.registerHelper('userGetAvatar', function(type, avatar) {
	if ( avatar[type] && avatar[type].path) {
		return avatar[type].path;
	} else {
		return defaultAvatarPath[type];
	}
});