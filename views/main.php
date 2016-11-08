<form action="<?php echo $action; ?>" method="POST" enctype="multipart/form-data" onsubmit="submit_ajax(this)">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    <input type="hidden" name="step" value="<?php echo $step; ?>">
  <fieldset>
    <label for="sourceFile<?php echo $tag; ?>"><?php echo $msgs['SOURCE_FILE']; ?>
      <input type="file" name="sourceFile<?php echo $tag; ?>"  id="sourceFile<?php echo $tag; ?>" value="<?php echo $sourceFile; ?>"/>
    </label>
    <?php if($targetFile){ ?>
    <label for="targetFile<?php echo $tag; ?>"><?php echo $msgs['TARGET_FILE']; ?>
      <a class="file" name="targetFile<?php echo $tag; ?>"  id="targetFile<?php echo $tag; ?>" href="<?php echo $targetFile; ?>"><?php echo $targetFile; ?></a>
    </label>
    <?php } ?>
  </fieldset>
  <fieldset>
    <input type="submit"><?php echo $msgs['SUBMIT_BUTTON_TEXT']; ?></input>
  </fieldset>
  <fieldset>
    <?php
    foreach ($fields as $key => $val) {
        ?>
    <label for="<?php echo $key.$tag; ?>"><?php echo $msgs[$key]; ?>
      <input type="text" name="<?php echo $key.$tag; ?>" id="<?php echo $key.$tag; ?>" value="<?php echo $val; ?>"/>
    </label>
    <?php

    }
    ?>
  </fieldset>
</form>
<script>
    function submit_ajax(formElement){
        var elements = formElement.getElementsByTagName('input');
        var queryString = '';
        for(var i=0;i<elements.length;i++){
            queryString += elements[i].name + '=' + elements[i].value + '&';
        }
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                updateForm(JSON.parse(this.responseText));
            }
        };
        switch(formElement.getAttribute('method')){
            case "GET":
                queryString = formElement.getAttribute('action') + '?' + queryString;
                xhttp.open(formElement.getAttribute('method'), queryString, true);
                xhttp.send();
                break;
            case "POST":
            default:
                xhttp.open(formElement.getAttribute('method'), formElement.getAttribute('action'), true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send(queryStringd);
                break;
        }

    }
    function updateForm(values){
        for(var i in values){
            var element = document.getElementById(i);
            if(typeof(element) != 'undefined') {
                switch(element.nodeValue){
                    case 'input':
                        element.setAttribute('value', values[i]);
                        break;
                    case 'textarea':
                        element.innerText = values[i];
                        break;
                    default:
                        var node = document.createElement("input");
                        node.setAttribute('value', values[i]);
                        element.appendChild(node);
                        break;

                }
            }
            else{

            }
        }
    }
</script>
