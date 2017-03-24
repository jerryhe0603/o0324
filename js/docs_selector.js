
BindBootBox();
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
    //if alrdy in #target, return false
    var inputId = $temp.find('input').attr('id');
    if($('#target #'+inputId).length>0){
        console.log('draged object repeat');
        clearInterval(delayed);
        return false;
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
        
      });
    }, delays[type]);

    $(document).mouseup(function () {
      clearInterval(delayed);
      return false;
    });
    $(this).mouseout(function () {
      clearInterval(delayed);
    });
  });
  
  
 
});
function CheckElement(){
    console.log('check element');
    $.each( $( '#require_element_mapping' ).children(), function( idx, elem ) {
        var testId = elem.id;
        if(testId.match('require_element_([0-9]+)')){
            var element_mapping_no = testId.split('_')[2];
            //check if $('#default #element_mapping_no_x') exist , turn elem color to blue
            if($('#default_element #element_mapping_no_'+element_mapping_no).length>0){
                $(elem).attr('class','blue');
            }else if($('#target #element_mapping_no_'+element_mapping_no).length>0){
                //else if $('#target #element_mapping_no_x') exist , turn elem color to blue
                $(elem).attr('class','blue');
            }else if($('#doc_element #element_mapping_no_'+element_mapping_no).length>0){
                //else if $('#target #element_mapping_no_x') exist , turn elem color to blue
                $(elem).attr('class','blue');
            }else{
                $(elem).attr('class','red');
            }
        } 
    });
    $.each( $( '#script_file_element' ).children(), function( idx, elem ) {
        var elemVal = $(elem).attr('value');
        $(elem).removeClass('blue');
        $(elem).addClass('red');
        //console.log(elemVal);
        $.each( $( '#target input'), function( idx2, elem2 ) {
            var elem2Id = elem2.id;
            if(elem2Id.match('file_docs_([0-9]+)_element')){
                console.log(elem2);
                console.log($(elem2).val());
                if($(elem2).val() == elemVal)
                    $(elem).addClass('blue');
                return;
            }
        });
    });
}

function BindScriptChange(){
    $('#script_selector').change(function(){
        ScriptChange();
    });
}

function BindBootBox(){
    $(document).on('click','.boot_box_button',function(){
        //先取此button是屬於哪個file_doc
        var buttonId = this.id;
        var file_doc_no = buttonId.split('_')[3];
        var html = '<div><label>選擇對應欄位</label></div>'+
                   '<div>'+
                        '<select id="element_no">';
        $('.script_file_element').each(function( idx, element) {
            html += '<option value="'+element.id.split('_')[3]+'">'+element.innerHTML+'</option>';
        });
                
        html += '<select>'+
                   '</div>';
        bootbox.dialog({
                message: html,
                    buttons: {
                            success:{
                                    label: "確定",
                                    className: "btn-small btn-success",
                                    callback: function() {
                                            $('#file_docs_'+file_doc_no+'_element').val($('#element_no').val());
                                            $('#file_docs_'+file_doc_no+'_element_name').html($('#element_no').find("option:selected").text());
                                            CheckElement();
                                    }
                            },
                            cancel:{
                                    label: "取消",
                                    className:"btn-small btn-danger",
                                    "callback": function() {
                                            //Example.show("uh oh, look out!");
                                    }        
                            }
                    }
            }) ;     
    });
}

function ScriptChange(){
    var script_no ='';
    $('#script_selector').each(function( idx, selector) {
        script_no = selector.options[selector.selectedIndex].value;
    });
    //console.log(script_no);
    $('#require_element_mapping').html('');
    $('#script_file_element').html('');
    
    $.each($('#target .component'),function(idx, elem){
        elem.remove();
    });
    
    $.each($('#target .file_element_area'),function(idx, elem){
        elem.remove();
    });
    
    $.ajax({
        url: '../ajax/ajax.CScriptElement.php?action=get_element',
        type: 'post',
        data: {"text": script_no},
        dataType: 'json',
        success: function(json) {
            console.log(json);
            //require_nonfile_element
            var str = "";
            for(var i =0; i < json.require.length; i++) {
                str +='&nbsp<span class="red" id="require_element_'+json['require'][i]['element_mapping_no']+'">'+json['require'][i]['element_mapping_name']+'</span>&nbsp';
            }
            $('#require_element_mapping').html(str);
            CheckElement();
            //require_file_element
            str = "";
            for(var i =0; i < json.file.length; i++) {
                str +='&nbsp<span class="red script_file_element" id="script_file_element_'+json['file'][i]['element_no']+'" value="'+json['file'][i]['element_no']+'">'+json['file'][i]['element_mapping_name']+'</span>&nbsp';
            }
            $('#script_file_element').html(str);
        }
    });
}

