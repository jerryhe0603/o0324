$(document).ready(function(){
  
  $("form").delegate(".component", "mousedown", function(md){
    $(".popover").hide();
	
    md.preventDefault();
    var tops = [];
    var mouseX = md.pageX;
    var mouseY = md.pageY;
    var $temp;
    var timeout;
    var $this = $(this);
    var delays = {
      main: 0,
      form: 200
    }
    var type;

    if($this.parent().parent().parent().parent().attr("id") === "components"){
      type = "main";
    } else {
      type = "form";
    }

    var delayed = setTimeout(function(){
      if(type === "main"){
        $temp = $("<form class='form-horizontal  ' id='temp'>").append($this.clone());
      } else {
        if($this.attr("id") !== "legend"){
          $temp = $("<form class='form-horizontal  ' id='temp'>").append($this);
        }
      }
console.log($temp.html());
      $("body").append($temp);

      $temp.css({"position" : "absolute",
                 "top"      : mouseY - ($temp.height()/2) + "px",
                 "left"     : mouseX - ($temp.width()/2) + "px",
                 "opacity"  : "0.9"}).show()

      var half_box_height = ($temp.height()/2);
      var half_box_width = ($temp.width()/2);
      var $target = $("#target");
      var tar_pos = $target.position();
      var $target_component = $("#target .component");

      $(document).delegate("body", "mousemove", function(mm){

        var mm_mouseX = mm.pageX;
        var mm_mouseY = mm.pageY;

        $temp.css({"top"      : mm_mouseY - half_box_height + "px",
          "left"      : mm_mouseX - half_box_width  + "px"});

        if ( mm_mouseX > tar_pos.left &&
          mm_mouseX < tar_pos.left + $target.width() + $temp.width()/2 &&
          mm_mouseY > tar_pos.top &&
          mm_mouseY < tar_pos.top + $target.height() + $temp.height()/2
          ){
            $("#target").css("background-color", "#fafdff");
            $target_component.css({"border-top" : "1px solid white", "border-bottom" : "none"});
            tops = $.grep($target_component, function(e){
              return ($(e).position().top -  mm_mouseY + half_box_height > 0 && $(e).attr("id") !== "legend");
            });
            if (tops.length > 0){
              $(tops[0]).css("border-top", "1px solid #22aaff");
            } else{
              if($target_component.length > 0){
                $($target_component[$target_component.length - 1]).css("border-bottom", "1px solid #22aaff");
              }
            }
          } else{
            $("#target").css("background-color", "#fff");
            $target_component.css({"border-top" : "1px solid white", "border-bottom" : "none"});
            $target.css("background-color", "#fff");
          }
      });

      $("body").delegate("#temp", "mouseup", function(mu){
        mu.preventDefault();

        var mu_mouseX = mu.pageX;
        var mu_mouseY = mu.pageY;
        var tar_pos = $target.position();

        $("#target .component").css({"border-top" : "1px solid white", "border-bottom" : "none"});

        // acting only if mouse is in right place
        if (mu_mouseX + half_box_width > tar_pos.left &&
          mu_mouseX - half_box_width < tar_pos.left + $target.width() &&
          mu_mouseY + half_box_height > tar_pos.top &&
          mu_mouseY - half_box_height < tar_pos.top + $target.height()
          ){

			
			var id = $temp.find('[element-type]').attr('id');
			
			if($('#target').find('#'+id).size() > 0) {
				$target.css("background-color", "#fff");
				$(document).undelegate("body", "mousemove");
				$("body").undelegate("#temp","mouseup");
				$("#target .component").popover({trigger: "manual"});
				$temp.remove();
				
				return;
			}
			
			$temp.attr("style", null);
            // where to add
            if(tops.length > 0){
              $($temp.html()).insertBefore(tops[0]);
            } else {
              $("#target fieldset").append($temp.html());
              $('[data-rel=popover]').popover({html:true});
            }
          } else {
            // no add
            $("#target .component").css({"border-top" : "1px solid white", "border-bottom" : "none"});
            tops = [];
          }

        //clean up & add popover
        $target.css("background-color", "#fff");
        $(document).undelegate("body", "mousemove");
        $("body").undelegate("#temp","mouseup");
        $("#target .component").popover({trigger: "manual"});
        $temp.remove();
        //genSource();
      });
    }, delays[type]);

    $(document).mouseup(function () {
      clearInterval(delayed);
      CheckElement();
      return false;
    });
    $(this).mouseout(function () {
      clearInterval(delayed);
      CheckElement();
    });
  });
  
  //activate legend popover
  $("#target .component").popover({trigger: "manual"});
  //popover on click event
  $("#target").delegate(".component", "click mouseenter mouseleave", function(e){

  if(e.type == "mouseenter") {
		$(this).find('.edit').show();
	} else if(e.type == "mouseleave") {
		$(this).find('.edit').hide();
	} else {
        e.preventDefault();
    $(".popover").hide();
    $(".popover").remove();
    var $active_component = $(this);
    $active_component.popover("show");

	var valtypes = $active_component.find("[element-type]");
	
    $.each(valtypes, function(i,e){
      var valtype = $(e).attr("element-type");
	  var valID = $(e).attr("id");
	 
      var val;
	  
      if(valtype ==="text"){
			
		val = $(e).val();
		
		$('.popover #'+valID).val(val);
       
      } else if(valtype==="checkbox"){
        if($(e).is(":checked")){	
			$('.popover #'+valID).filter('[value="'+$(e).val()+'"]').prop("checked", true);
		}
      }  else if(valtype==="switch") {

           if($(e).is(":checked")){   
    

              $('.popover #'+valID).filter('[value="'+$(e).val()+'"]').prop("checked", true);
           }
            else{
              $('.popover #'+valID).prop("checked", false);
              
            }
      }else if(valtype==="option"){
		
		if($(e).find('option').is(':selected')) {		
			
			$('.popover #'+valID).find(':contains("'+$(e).find('option:selected').text()+'")').prop("selected", true);
		}
     
      }else if(valtype==="radio"){
        if($(e).is(":checked")){	
			$('.popover #'+valID).filter('[value="'+$(e).val()+'"]').prop("checked", true);
		}
      
      }else if(valtype==="textarea"){
        val = $(e).text();
		$('.popover #'+valID).val(val);

      }
    });

    $(".popover").on("click", ".btn-danger", function(e){

        e.preventDefault();
        $active_component.popover("hide");
    });

        $(".popover").undelegate(".btn-success").delegate(".btn-success", "click", function(e) {
 
            e.preventDefault();
            var valtypes = $('.popover ').find("[element-type]");

            $.each(valtypes, function(i,e) {
                var valtype = $(e).attr("element-type");
                var valID = $(e).attr("id");
                
                var value = $active_component.find('#'+valID);
		
                if(valtype==="checkbox") {
		if($(e).is(":checked")){	 		
                     $(value).filter('[value="'+$(e).val()+'"]').prop("checked", true);
                        }
                else{

                $(value).filter('[value="'+$(e).val()+'"]').prop("checked", false);
                 }
      } 
      else if(valtype==="switch") {
           if($(e).prop("checked")){   
                $(value).removeAttr('checked').val('1').prop('checked',true);     
           }
            else{
              //$(value).removeAttr('checked').prop("checked", false).attr('checked', false);
              $(value).removeAttr('checked').val('0');
              console.log('false');
            }
      }else if (valtype==="option"){
		
		if($(e).find('option').is(':selected')) {		
			
			$(value).find(':contains("'+$(e).find('option:selected').text()+'")').prop("selected", true);
		}
		
      } else if (valtype==="textarea"){
			$(value).text($(e).val());			
           
      }else if (valtype==="text"){
			$(value).prop('value', $(e).val());	
      } else if (valtype==="radio"){		 
        if($(e).is(":checked")){	
          $(value).filter('[value="'+$(e).val()+'"]').prop("checked", true);
        }else {
            $(value).filter('[value="'+$(e).val()+'"]').prop("checked", false);
        }			
      }
    $active_component.popover("hide");
    });   //each
    });
	}
        CheckElement();
  });
  
 
});
function CheckElement(){
    console.log('checkElement');
    $.each( $( '#require_element_mapping' ).children(), function( idx, elem ) {
        var testId = elem.id;
        if(testId.match('require_element_([0-9]+)')){
            var element_mapping_no = testId.split('_')[2];
            console.log('check require element_mapping_no: '+element_mapping_no);
            //check if $('#default #element_mapping_no_x') exist , turn elem color to blue
            if($('#default_element #element_mapping_no_'+element_mapping_no).length>0){
                console.log('checked in default');
                $(elem).attr('class','blue');
            }else if($('#target #element_mapping_no_'+element_mapping_no).length>0){
                //else if $('#target #element_mapping_no_x') exist , turn elem color to blue
                console.log('checked in target');
                $(elem).attr('class','blue');
            }else if($('#doc_element #element_mapping_no_'+element_mapping_no).length>0){
                //else if $('#target #element_mapping_no_x') exist , turn elem color to blue
                console.log('checked in doc_element');
                $(elem).attr('class','blue');
            }else{
                console.log('checked not exist');
                $(elem).attr('class','red');
            }
        } 
    });
}

function BindScriptChange(){
    $('#script_selector').change(function(){
        console.log('a script selected');
        ScriptChange();
    });
}

function ScriptChange(){
    $('#script_selector').each(function( idx, selector) {
        script_no = selector.options[selector.selectedIndex].value;
    });
    //console.log(script_no);
    $('#require_element_mapping').html('');
    $.ajax({
        url: './get_require_elements.php',
        type: 'post',
        //data: {"text": selected_boards},
        data: {"text": script_no},
        dataType: 'json',
        success: function(json) {
            var str = "";
            for(var i =0; i < json.length; i++) {
                str +='&nbsp<span class="red" id="require_element_'+json[i]['element_mapping_no']+'">'+json[i]['element_mapping_name']+'</span>&nbsp';
            }
            $('#require_element_mapping').html(str);
            CheckElement();
        }
    });
}

