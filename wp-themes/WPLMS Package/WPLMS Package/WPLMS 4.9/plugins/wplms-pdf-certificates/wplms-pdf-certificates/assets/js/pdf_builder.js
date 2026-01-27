jQuery(document).ready(function($){

	var max_width = $('.pdf_builder_main').width();
	var max_height = $('.pdf_builder_main').height();

	var font_list = [
		{'value':'times','label':'Times New Roman'},
		{'value':'timesB','label':'Times New Roman Bold'},
		{'value':'courier','label':'Courier'},
		{'value':'courierB','label':'Courier Bold'},
		{'value':'helvetica','label':'Helvetica'},
		{'value':'helveticaB','label':'Helvetica Bold'},
	];
	if(typeof  window.pdf_builder.fonts !== 'undefined' && window.pdf_builder.fonts.length){
		pdf_builder.fonts.map(function (font){
			font_list.push({'label':font.name,'value':font.path})
			return font;
		});
	}
	var certificate_json =[];
	if(typeof  window.certificate_json !== 'undefined'){
		certificate_json = window.certificate_json;
	}

	let width = $('#vibe_certificate_width').val();
	let height = $('#vibe_certificate_height').val();

	if(parseInt(width,10) <= 0){
		width = 595;
	} 
	if(parseInt(height,10) <= 0){
		height = 842;
	} 

	let saved_certificate = localStorage.getItem('saved_certificate');
	if(saved_certificate !== null){
		if( saved_certificate !== JSON.stringify(certificate_json)){

			new Promise(function(resolve){
				$('.pdf_elements').append('<div class="pdf_element restore_element button">'+pdf_builder.restore+'</div>');
				resolve();	
			}).then(function(){

				$('.pdf_builder').trigger('init');
			});
		}else{
			setTimeout(function(){
				$('.pdf_builder').trigger('init');	
			},300);
			
		}
	}else{
		setTimeout(function(){
			$('.pdf_builder').trigger('init');	
		},300);
	}

	$('.save_element').on('click',function(){
		let defText = $('.save_element').text();
		var $this = $(this);

		if(!$this.hasClass('loading')){
			$this.text(window.pdf_builder.saving);
			$this.addClass('loading');
			$.ajax({
	          	type: "POST",
	          	url: ajaxurl,
	          	data: { action: 'save_pdf_builder',
	                  security:$('#save_pdf_builder').val(),
	                  id:window.pdf_builder.id,
	                  certificate_json: JSON.stringify(certificate_json)
	                },
	          	cache: false,
	          	success: function (html) {
	            	$this.text(html);
	            	setTimeout(function(){
	            		$this.text(defText);
	            		$this.removeClass('loading');
	            	},3000);
	          	}
	        });
		}
	});
	

	$('.pdf_builder_main').width(width);
	$('.pdf_builder_main').height(height);

	$('#certificate-builder').css('min-width',(parseInt(width,10)+30)+'px');

	if($('#image_vibe_background_image').attr('src').length){
		$('.pdf_builder_main').css('background','url("'+$('#image_vibe_background_image').attr('src')+'") no-repeat left top');
		$('.pdf_builder_main').css('background-size','100%');
	};

	

	$('.text_element').on('click',function(){
		max_width = $('.pdf_builder_main').width();
		max_height = $('.pdf_builder_main').height();
		$('.popup_element_wrapper').remove();
		let element = '<div class="popup_element_wrapper"><div class="popup_element new">\
			<div class="left_block"><textarea>'+pdf_builder.default_text+'</textarea></div>\
			<div class="right_block"><input class="top" placeholder="'+pdf_builder.top_margin+'" />\
			<input class="left" placeholder="'+pdf_builder.left_margin+'" />\
			<div><label>'+pdf_builder.width+'</label><input type="text" value="0" class="width slider_input" /><div class="font_size_slider" min="1" max="'+max_width+'" value="0"></div></div>\
			<div><label>'+pdf_builder.height+'</label><input type="text" value="0" class="height slider_input" /><div class="font_size_slider" min="1" max="'+max_height+'" value="0"></div></div>\
			</div>\
			<div class="controls"><ul>';
		element += '<li><label>'+pdf_builder.font_size+'</label><input type="text" value="14" class="font_size slider_input"><div class="font_size_slider" min="1" max="100" value="14"></div></li>';
		element += '<li><label>'+pdf_builder.font_color+'</label><input type="text" value="#222222" class="font_color"></li>';
		element += '<li><label>'+pdf_builder.font_style+'</label><select class="font_style"><option value="none">None</option><option value="underline">Underline</option><option value="italics">Italics</option></select></li>';
		element += '<li><label>'+pdf_builder.font_family+'</label><select class="font_family">';
		font_list.map(function(font){
			element += '<option value="'+font.value+'">'+font.label+'</option>';
		});
		element += '</select></li>';
		element += '<li><label>'+pdf_builder.text_align+'</label><select class="text_align"><option value="left">'+pdf_builder.align_left+'</option><option value="right">'+pdf_builder.align_right+'</option><option value="center">'+pdf_builder.align_center+'</option></select>';
		element +='</ul></div><a class="button-primary add_text">'+pdf_builder.add_text+'</a><div class="close_element dashicons dashicons-no"></div></div></div></div>';
		$('.pdf_builder').append(element);
		$('.pdf_builder').trigger('adding');
	});

	$('.image_element').on('click',function(){
		$('.popup_element_wrapper').remove();
		max_width = $('.pdf_builder_main').width();
		max_height = $('.pdf_builder_main').height();
		let element = '<div class="popup_element_wrapper"><div class="popup_element new">\
			<div class="left_block"><img src="'+pdf_builder.default_image_url+'" /><a class="upload_image">'+pdf_builder.upload_image+'</a><input type="hidden" value="'+pdf_builder.default_image_url+'" class="image_path" /></div>\
			<div class="right_block"><input class="top" placeholder="'+pdf_builder.top_margin+'" />\
			<input class="left" placeholder="'+pdf_builder.left_margin+'" />\
			<div><label>'+pdf_builder.width+'</label><input type="text" value="0" class="width slider_input" /><div class="font_size_slider" min="1" max="'+max_width+'" value="0"></div></div>\
			<div><label>'+pdf_builder.height+'</label><input type="text" value="0" class="height slider_input" /><div class="font_size_slider" min="1" max="'+max_height+'" value="0"></div></div>\
			</div>\
			<div class="controls"><ul>';

		
		element += '<li><label>'+pdf_builder.border_radius+'</label><input type="text" value="0" class="border_radius slider_input"><div class="font_size_slider" min="1" max="100" value="0"></div></li>';
		element +='</ul></div><a class="button-primary add_image">'+pdf_builder.add_image+'</a><div class="close_element dashicons dashicons-no"></div>\
			</div></div></div>';
 		
 		$('.pdf_builder').append(element);

		$('.pdf_builder').trigger('adding');
	});
	$('.pdf_builder').on('adding',function(){
		
		startFunctions();
		

		$('.add_text').on('click',function(){
			let value = $(this).parent().find('textarea').val();
			let top = $(this).parent().find('.top').val();
			let left = $(this).parent().find('.left').val();
			let w = $(this).parent().find('.width').val();
			let h = $(this).parent().find('.height').val();
			let size = $(this).parent().find('.font_size').val();
			let color = $(this).parent().find('.font_color').val();
			let style = $(this).parent().find('.font_style').val();
			let family = $(this).parent().find('.font_family').val();
			let align = $(this).parent().find('.text_align').val();
			let html = '<div class="inline_text_element" data-id="'+certificate_json.length+'" style="position:absolute;top:'+top+'px;left:'+left+'px;width:'+w+'px;height:'+h+'px;font-size:'+size+'px;color:'+color+';font-family:'+getFamily(family)+';text-align:'+align+';text-decoration:'+style+';">'+value+'</div>';
			certificate_json.push({
				'type':'text',
				'top':top,
				'left':left,
				'width':w,
				'height':h,
				'size':size,
				'color':color,
				'family':family,
				'style':style,
				'value':value,
				'align':align
			});
			localStorage.setItem('saved_certificate',JSON.stringify(certificate_json));
			$('.pdf_builder_main').append(html);
			$('.popup_element_wrapper').remove();

			$('.pdf_builder').trigger('ready');
		});

		$('.add_image').on('click',function(){
			let value = $(this).parent().find('.image_path').val();
			let top = $(this).parent().find('.top').val();
			let left = $(this).parent().find('.left').val();
			let w = $(this).parent().find('.width').val();
			let h = $(this).parent().find('.height').val();
			let radius = $(this).parent().find('.border_radius').val();
			let html = '<div class="inline_image_element" data-id="'+certificate_json.length+'" style="position:absolute;top:'+top+'px;left:'+left+'px;'+((parseInt(w))?'width:'+w+'px':'')+';'+(parseInt(h)?'height:'+h+'px':'')+';border-radius:'+radius+'px;"><img src="'+value+'" /></div>';
			certificate_json.push({
				'type':'image',
				'top':top,
				'left':left,
				'width':w,
				'height':h,
				'radius':radius,
				'value':value
			});
			localStorage.setItem('saved_certificate',JSON.stringify(certificate_json));
			$('.pdf_builder_main').append(html);
			$('.popup_element_wrapper').remove();
			$('.pdf_builder').trigger('ready');
		});


	});

	$('.pdf_builder').on('editing',function(){

		startFunctions();

		

		$('.edit_text').on('click',function(){

			let value = $(this).parent().find('textarea').val();
			let top = $(this).parent().find('.top').val();
			let left = $(this).parent().find('.left').val();
			let w = $(this).parent().find('.width').val();
			let h = $(this).parent().find('.height').val();

			let size = $(this).parent().find('.font_size').val();
			let color = $(this).parent().find('.font_color').val();
			let style = $(this).parent().find('.font_style').val();
			let family = $(this).parent().find('.font_family').val();

			let align = $(this).parent().find('.text_align').val();
			let id = $(this).parent().attr('data-id');

			$('.inline_text_element[data-id="'+id+'"]').css('top',top+'px');
			$('.inline_text_element[data-id="'+id+'"]').css('left',left+'px');
			$('.inline_text_element[data-id="'+id+'"]').css('width',w+'px');
			$('.inline_text_element[data-id="'+id+'"]').css('height',h+'px');

			$('.inline_text_element[data-id="'+id+'"]').css('font-size',size+'px');
			$('.inline_text_element[data-id="'+id+'"]').css('color',color);
			$('.inline_text_element[data-id="'+id+'"]').css('text-decoration',style);
			$('.inline_text_element[data-id="'+id+'"]').css('font-family',getFamily(family));

			$('.inline_text_element[data-id="'+id+'"]').css('text-align',align);

			$('.inline_text_element[data-id="'+id+'"]').html(value);
			certificate_json[parseInt(id,10)]={
				'type':'text',
				'top':top,
				'left':left,
				'width':w,
				'height':h,
				'size':size,
				'color':color,
				'family':family,
				'style':style,
				'value':value,
				'align':align
			};

			console.log(certificate_json[parseInt(id,10)]);
			localStorage.setItem('saved_certificate',JSON.stringify(certificate_json));
			$('.popup_element_wrapper').remove();

			$('.pdf_builder').trigger('ready');
		});

		$('.edit_image').on('click',function(){
			let value = $(this).parent().find('.image_path').val();
			let top = $(this).parent().find('.top').val();
			let left = $(this).parent().find('.left').val();
			let w = $(this).parent().find('.width').val();
			let h = $(this).parent().find('.height').val();

			let radius = $(this).parent().find('.border_radius').val();
			

			let id = $(this).parent().attr('data-id');

			$('.inline_image_element[data-id="'+id+'"]').css('top',top+'px');
			$('.inline_image_element[data-id="'+id+'"]').css('left',left+'px');
			if(w){
				$('.inline_image_element[data-id="'+id+'"]').css('width',w+'px');	
			}
			if(h){
				$('.inline_image_element[data-id="'+id+'"]').css('height',h+'px');
			}
			
			

			$('.inline_image_element[data-id="'+id+'"]').find('img').attr('src',value);
			certificate_json[parseInt(id,10)]={
				'type':'image',
				'top':top,
				'left':left,
				'width':w,
				'height':h,
				'radius':radius,
				'value':value
			};
			localStorage.setItem('saved_certificate',JSON.stringify(certificate_json));
			$('.popup_element_wrapper').remove();

			$('.pdf_builder').trigger('ready');
		});

		$('.remove_image,.remove_text').on('click',function(){
			let id = parseInt($(this).parent().attr('data-id'),10);
			certificate_json.splice(id,1);
			localStorage.setItem('saved_certificate',JSON.stringify(certificate_json));
			$('.inline_text_element[data-id="'+id+'"]').remove();
			$('.inline_image_element[data-id="'+id+'"]').remove();
			$('.popup_element_wrapper').remove();
		});
	});



	$('.pdf_builder').on('init',function(){

		$('.restore_element').on('click',function(){
			certificate_json = JSON.parse(localStorage.getItem('saved_certificate'));
			sync_certificate_json();
		});

		sync_certificate_json();
	});

	$('.pdf_builder').on('ready',function(){

		$( ".inline_text_element,.inline_image_element" ).draggable({ 
			containment: ".pdf_builder_main", 
			scroll: false ,
			drag: function() {
				var offset = $(this).position();
		        var xPos = offset.left;
		        var yPos = offset.top;
		        $(this).css('left',offset.left);
		        $(this).css('top',offset.top);
		        certificate_json[parseInt($(this).attr('data-id'))].left=offset.left;
		        certificate_json[parseInt($(this).attr('data-id'))].top=offset.top;
		        $('.popup_element[data-id="'+parseInt($(this).attr('data-id'))+'"] .top').val(offset.top);
		        $('.popup_element[data-id="'+parseInt($(this).attr('data-id'))+'"] .left').val(offset.left);
			}
		});


		$('.inline_text_element').on('click',function(){
			$('.popup_element_wrapper').remove();
			let id = parseInt($(this).attr('data-id'));
			
			$('.pdf_builder_main').find('.active').removeClass('active');
			$(this).addClass('active');
			let element = '<div class="popup_element_wrapper">\
			<div class="popup_element" data-id="'+id+'">\
			<div class="left_block"><textarea>'+certificate_json[id].value+'</textarea></div>\
			<div class="right_block"><input class="top" placeholder="'+pdf_builder.top_margin+'" value="'+certificate_json[id].top+'" />\
			<input class="left" placeholder="'+pdf_builder.left_margin+'" value="'+certificate_json[id].left+'" />\
			<div><label>'+pdf_builder.width+'</label><input type="text" value="'+certificate_json[id].width+'" class="width slider_input"><div class="font_size_slider" min="1" max="800" value="'+certificate_json[id].width+'"></div></div>\
			<div><label>'+pdf_builder.height+'</label><input type="text" value="'+certificate_json[id].height+'" class="height slider_input"><div class="font_size_slider" min="1" max="800" value="'+certificate_json[id].height+'"></div></div>\
			</div>\
			<div class="controls"><ul>';

			element += '<li><label>'+pdf_builder.font_size+'</label><input type="text" value="'+certificate_json[id].size+'" class="font_size slider_input"><div class="font_size_slider" min="1" max="100" value="'+certificate_json[id].size+'"></div></li>';
			element += '<li><label>'+pdf_builder.font_color+'</label><input type="text" value="'+certificate_json[id].color+'" class="font_color"></li>';
			element += '<li><label>'+pdf_builder.font_style+'</label><select class="font_style"><option value="none">None</option><option value="underline" '+((certificate_json[id].style === 'underline')?'selected':'')+'>Underline</option><option value="italics" '+((certificate_json[id].style === 'italics')?'selected':'')+'>Italics</option></select></li>';
			element += '<li><label>'+pdf_builder.font_family+'</label><select class="font_family">';
			font_list.map(function(font){
				element += '<option value="'+font.value+'" '+((certificate_json[id].family === font.value)?'selected':'')+'>'+font.label+'</option>';
			});
			element += '</select></li>';
			element += '<li><label>'+pdf_builder.text_align+'</label><select class="text_align"><option value="left">'+pdf_builder.align_left+'</option><option value="right" '+((certificate_json[id]['align'] === 'right')?'selected':'')+'>'+pdf_builder.align_right+'</option><option value="center" '+((certificate_json[id]['align'] === 'center')?'selected':'')+'>'+pdf_builder.align_center+'</option></select>';
		
			element +='</ul></div><a class="button-primary edit_text">'+pdf_builder.edit_text+'</a><a class="button remove_text">'+pdf_builder.remove+'</a><div class="close_element dashicons dashicons-no"></div></div></div></div>';
		
			$('.pdf_builder').append(element);
			$('.pdf_builder').trigger('editing');
		});

		$('.inline_image_element').on('click',function(){
			$('.popup_element_wrapper').remove();
			let id = $(this).attr('data-id');

			$('.pdf_builder_main').find('.active').removeClass('active');
			$(this).addClass('active');

			let element = '<div class="popup_element_wrapper"><div class="popup_element" data-id="'+id+'">\
			<div class="left_block"><img src="'+certificate_json[id].value+'" /><a class="upload_image">'+pdf_builder.upload_image+'</a><input type="hidden" value="'+certificate_json[id].value+'" class="image_path" /></div>\
			<div class="right_block"><input class="top" placeholder="'+pdf_builder.top_margin+'" value="'+certificate_json[id].top+'" />\
				<input class="left" placeholder="'+pdf_builder.left_margin+'" value="'+certificate_json[id].left+'" />\
				<div><label>'+pdf_builder.width+'</label><input type="text" value="'+certificate_json[id].width+'" class="width slider_input"><div class="font_size_slider" min="1" max="800" value="'+certificate_json[id].width+'"></div></div>\
				<div><label>'+pdf_builder.height+'</label><input type="text" value="'+certificate_json[id].height+'" class="height slider_input"><div class="font_size_slider" min="1" max="800" value="'+certificate_json[id].height+'"></div></div>\
			</div>\
			<div class="controls"><ul>';

			element += '<li><label>'+pdf_builder.border_radius+'</label><input type="text" value="'+certificate_json[id].radius+'" class="border_radius slider_input"><div class="font_size_slider" min="1" max="100" value="'+certificate_json[id].radius+'"></div></li>';
			element +='</ul></div><a class="button-primary edit_image">'+pdf_builder.edit_image+'</a><a class="button remove_image">'+pdf_builder.remove+'</a><div class="close_element dashicons dashicons-no"></div>\
				</div></div></div>';

			
			$('.pdf_builder').append(element);
			$('.pdf_builder').trigger('editing');
		});
	});



var sync_certificate_json = function(){
	
	if(typeof window.certificate_json !== "undefined" && window.certificate_json.length){
		
		let x =  new Promise(function(resolve){

				window.certificate_json.map(function(item,index){

					if(item.type == "text"){
						
						if($('.inline_text_element[data-id="'+index+'"]').length){
							$('.inline_text_element[data-id="'+index+'"]').css('top',item.top+'px');
							$('.inline_text_element[data-id="'+index+'"]').css('left',item.left+'px');
							$('.inline_text_element[data-id="'+index+'"]').css('width',item.width+'px');
							$('.inline_text_element[data-id="'+index+'"]').css('height',item.height+'px');

							$('.inline_text_element[data-id="'+index+'"]').css('font-size',item.size+'px');
							$('.inline_text_element[data-id="'+index+'"]').css('color',item.color);
							$('.inline_text_element[data-id="'+index+'"]').css('text-decoration',item.style);
							$('.inline_text_element[data-id="'+index+'"]').css('font-family',item.family);
							$('.inline_text_element[data-id="'+index+'"]').css('text-align',item.align);
							$('.inline_text_element[data-id="'+index+'"]').html(item.value);
						}else{
							let html = '<div class="inline_text_element" data-id="'+index+'" style="position:absolute;top:'+item.top+'px;left:'+item.left+'px;width:'+item.width+'px;height:'+item.height+'px;font-size:'+item.size+'px;color:'+item.color+';text-decoration:'+item.style+';font-family:'+item.family+'">'+item.value+'</div>';
							$('.pdf_builder_main').append(html);
						}
					}


					if(item.type == "image"){
						if($('.inline_image_element[data-id="'+index+'"]').length){
							
							$('.inline_image_element[data-id="'+index+'"]').css('top',item.top+'px');
							$('.inline_image_element[data-id="'+index+'"]').css('left',item.left+'px');
							$('.inline_image_element[data-id="'+index+'"]').css('width',item.width+'px');
							$('.inline_image_element[data-id="'+index+'"]').css('height',item.height+'px');
							$('.inline_image_element[data-id="'+index+'"]').css('border-radius',item.radius+'px');
							$('.inline_image_element[data-id="'+index+'"]').find('img').attr('src',item.value);
						}else{
							let html = '<div class="inline_image_element" data-id="'+index+'" style="position:absolute;top:'+item.top+'px;left:'+item.left+'px;width:'+item.width+'px;height:'+item.height+'px;"><img src="'+item.value+'" /></div>';
							$('.pdf_builder_main').append(html);
						}
					}

					if((index +1) === window.certificate_json.length){
						resolve();
					}
				});
			}).then(function(){
				setTimeout(function(){
					$('.pdf_builder').trigger('ready');	
				},300);
				
			});
	}
};

var getFamily = function(family){

	switch(family){
		case 'helvetica':
		case 'helveticaB':
			return 'Helvetica, sans-serif';
		break;
		case 'courieB':
		case 'courier':
			return 'Courier monospace';
		break;
		default:
			return '"Times New Roman", Times, serif';
		break;
	}
}

var uploadImage = function(){
	var media_uploader;
	$('.upload_image').on('click',function(){
		var button = jQuery( this );
	    if ( media_uploader ) {
	      media_uploader.open();
	      return;
	    }
	    // Create the media uploader.
	    media_uploader = wp.media.frames.media_uploader = wp.media({
	        // Tell the modal to show only images.
	        library: {
	            type: 'image',
	            query: false
	        },
	        multiple: false,
	        frame:    'post',
	        state: 'insert',
	    });

	    // Create a callback when the uploader is called
	    media_uploader.on( 'insert', function(selection) {
	        	
        	

            var state = media_uploader.state();
		    selection = selection || state.get('selection');

		    if (! selection) return;

		    var attachment = selection.first();
    		var display = state.display(attachment).toJSON(); 

    		var imgurl ='';
    		if(attachment.attributes.hasOwnProperty('sizes')){
    			imgurl = attachment.attributes.sizes[display.size].url;
    		}else{
    			if(attachment.attributes.hasOwnProperty('url')){
    				imgurl = attachment.attributes.url;
    			}
    		}
            button.parent().find('img').attr('src',imgurl);
           	button.parent().find('input.image_path').val(imgurl);
           	$('.inline_image_element[data-id="'+button.parent().parent().attr('data-id')+'"] img').attr('src',imgurl);

	    });
	    // Open the uploader
	    media_uploader.open();
	});
};


var startFunctions = function(){
	uploadImage();

	let object = null;
	if(!$('.popup_element').hasClass('new')){
		object = $('.inline_text_element[data-id="'+parseInt($('.popup_element').attr('data-id'),10)+'"]');
		if(!object.length){
			object = $('.inline_image_element[data-id="'+parseInt($('.popup_element').attr('data-id'),10)+'"]');
		}
	}

	$('textarea').on('input propertychange',function(){
		if(object){
			object.html($(this).val());
		}
	});
	$('input[type="hidden"]').on('change',function(){
		if(object){
			object.find(image).attr('src',$(this).val());
		}
	});
	$('.font_size_slider').each(function(){
		let $this = $(this);

		$(this).slider({
			value:$this.parent().find('.slider_input').val(),
			step:1,
			min:parseInt($this.attr('min'),10),
			max:parseInt($this.attr('max'),10),
			slide: function(event, ui) {
		        $this.parent().find('.slider_input').val(ui.value);
		        
		        if(object){
		        	if($this.parent().find('.font_size').length){
		        		object.css('font-size',ui.value+'px');
		        	}
		        	if($this.parent().find('.width').length){
		        		console.log('width');
		        		object.css('width',ui.value+'px');
		        	}
		        	if($this.parent().find('.height').length){
		        		object.css('height',ui.value+'px');
		        	}
		        	if($this.parent().find('.border_radius').length){
		        		object.css('border-radius',ui.value+'px');
		        	}
		        }
		    }
		});
	});

	$('.font_color').iris({
        width: 178,
        hide: true,
        change: function(event, ui) {
        	if(object){
        		object.css( 'color', ui.color.toString());
        	}
	    }
    });

	$('.font_style').on('change',function(){
		if(object){
			object.css('text-decoration',$(this).val());
		}
	});
	$('.font_family').on('change',function(){
		if(object){
			object.css('font-family',$(this).val());
		}
	});

	$('.text_align').on('change',function(){
		if(object){
			object.css('text-align',$(this).val());
		}
	});
    $('.font_color').on('click',function(){
    	if($(this).hasClass('active')){
    		$(this).removeClass('active');
    		$(this).iris('hide');	
    	}else{
    		$(this).addClass('active');
    		$(this).iris('show');	
    	}
    	
    });

	$('.close_element').on('click',function(){
		$('.popup_element_wrapper').remove();
	});
}

});