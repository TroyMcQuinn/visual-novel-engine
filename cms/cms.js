var current_frame = 0;

function eQ(x){
  return x.replace(/"/g, '&quot;');
}

function resetCurrents(){
  var template = {
    'background':'',
    'layers':['','','','','',''],
    'tweaks':[null,null,null,null,null,null],
    'music':'',
    'sfx':'',
    'video':'',
    'card':'',
    'scroll':'',
    'fade':''  
  };
  return template;
}

function updateEditor(active_frame_id){
  
  if(active_frame_id == null) active_frame_id = current_frame; // Default to currently targeted frame.
  
  current_frame = active_frame_id;
  document.getElementById('script_lines').innerHTML = '';

  // Reset currents
  currents = resetCurrents();
  
  // Iterate through frames.
  var frames = document.getElementById('script_editor_frames').getElementsByClassName('script_frame');

  for(var frame in frames){
    var frame_count = frame;
    var frame_id = frames[frame].id;
    var o = document.getElementById(frame_id);
    if(o != null){
      // Re-define frame as just id number (cardinal, not ordinal)
      frame = frame_id.split('_')[1];
      // Only show currently targeted frame.
      if(frame_id == 'frame_' + current_frame){
        o.style.display = 'table';
      } else {
        o.style.display = 'none';
      }
      
      // Display ordinal number for frame.
      document.getElementById('frame_ord_' + frame).innerHTML = frame_count;
      
      // Generate script lines view
      var title = document.getElementById('script_' + frame + '_title').value;
      var text = document.getElementById('script_' + frame + '_text').value;
      var has_notes = (document.getElementById('script_' + frame + '_notes').value != '');
      listLine(frame_count,frame,eQ(title),eQ(text),has_notes);
      
      // Show earlier-set fields.
      for(var c in currents){
        
        // Get earlier-set fields.
        if(c == 'layers'){
          for(var i=0;i<6;i++){
            if(currents[c][i]){
              if(currents[c][i] != '') el('current_layer_' + i + '_' + frame).innerHTML = curLink(c,currents[c][i]);
            }
          }
        } else {        
          if(currents[c] != '') el('current_' + c + '_' + frame).innerHTML = curLink(c,currents[c]);
        }        
        
        // Set currently-set if not left blank.
        if(c == 'layers'){
          for(var i=0;i<6;i++){
            if(el('script_' + frame + '_layers_' + i + '_image')){
              if(el('script_' + frame + '_layers_' + i + '_image').value != '') currents[c][i] = el('script_' + frame + '_layers_' + i + '_image').value;
            }
          }
        } else if(c == 'tweaks'){
          for(var i=0;i<6;i++){
            var k = i+1;
            if(el('script_' + frame + '_layers_' + k + '_tweaks_x')){
              if((el('script_' + frame + '_layers_' + k + '_tweaks_x').value != '') && (el('script_' + frame + '_layers_' + k + '_tweaks_y').value != '')){
                if(!currents[c][i]) currents[c][i] = {'x':0,'y':0};
              }
              if(el('script_' + frame + '_layers_' + k + '_tweaks_x').value != '') currents[c][i]['x'] = el('script_' + frame + '_layers_' + k + '_tweaks_x').value;
              if(el('script_' + frame + '_layers_' + k + '_tweaks_y').value != '') currents[c][i]['y'] = el('script_' + frame + '_layers_' + k + '_tweaks_y').value;
            }
          }
        } else {
          if((c == 'card') || (c == 'scroll')){
            var v = (el('script_' + frame + '_' + c).value == 'clear') ? 'clear' : 'set';
            if(el('script_' + frame + '_' + c).value != '') currents[c] = 'set';
          } else {
            if(el('script_' + frame + '_' + c).value != '') currents[c] = el('script_' + frame + '_' + c).value;
          }
        }
        
      }
      
      // Update preview window with the items set as 'current' right now.
      if(frame_id == 'frame_' + current_frame){
        updatePreview(currents); // Update the preview window
      }
      
    }
  }
  
}

function initEditor(){
  updateEditor(0);
}

function listLine(ord,id,title,text,has_notes){
  var o = document.getElementById('script_lines');
  var class_name = has_notes ? 'script_line note' : 'script_line';
  var line = "<a id=\"script_line_" + id + "\" class=\"" + class_name + "\" onclick=\"lineEdit(" + id + ");\">#" + ord + ': ';
  line += "<input type=\"text\" class=\"line_edit\" id=\"edit_title_" + id + "\" value=\"" +  title + "\" onchange=\"editTitle(" + id + ",this.value);\" />";
  line += "<input type=\"text\" class=\"line_edit line_edit_wide\" id=\"edit_text_" + id + "\" value=\"" +  text + "\" onchange=\"editText(" + id + ",this.value);\" />";
  o.innerHTML += line;
}

function showFrame(id){
  var prev_frame = current_frame;
  var pos = el('script_lines').scrollTop;
  updateEditor(id);
  el('script_lines').scrollTop = pos;
  highlightScriptLine(id);
}

function lineEdit(id){
  var t = document.activeElement.id;
  if(el(t)){
    var c = el(t).selectionStart;
  }
  showFrame(id);
  if(el(t)){
    el(t).focus();
    if(c){
      el(t).selectionStart = c;
    } else {
      el(t).selectionStart = el(t).value.length;
    }
  }
}

function el(x){ // Much-needed shorthand for document.getElementById();
  if(document.getElementById(x)){
    return document.getElementById(x);
  } else {
    return false;
  }
}

function moveFrame(direction,x){
  
  var editor = el('script_editor_frames');
  var frames = editor.getElementsByClassName('script_frame');
  
  switch(direction){
    
    case 'up':
    
      var swap_frame = frames[getPosForFrameID(x)];
      var prev_frame = frames[getPosForFrameID(getPreviousFrameID(x))];
      editor.insertBefore(swap_frame,prev_frame);      
    
    break;
    
    case 'down':
    default:
    
      var swap_frame = frames[getPosForFrameID(x)];
      var next_frame = frames[getPosForFrameID(getNextFrameID(x))];
      editor.insertBefore(next_frame,swap_frame);      
    
    break;
    
  }
  
  showFrame(x);
  
}

function addFrame(x){
  var templates = el('script_editor_frame_templates');
  var editor = el('script_editor_frames');
  var frames = editor.getElementsByClassName('script_frame');
  var new_frame = templates.getElementsByClassName('script_frame')[0];
  var new_frame_id = getNewFrameID();
  var next_frame_id = getNextFrameID(x);
  var newHTML = new_frame.outerHTML.toString();
  var regex = /TEMPLATEID/gi;
  newHTML = newHTML.replace(regex,(new_frame_id));
  var new_frame_element = document.createElement("table");
  var new_pos = getPosForFrameID(next_frame_id);
  if((frames[new_pos]) && (x != next_frame_id)){
    editor.insertBefore(new_frame_element,frames[new_pos]);
  } else {
    editor.appendChild(new_frame_element);
  }  
  new_frame_element.outerHTML = newHTML;
  updateEditor();
}

function removeFrame(x){
  if(!confirm('Are you sure?')){
    return false;
  }
  if(x == getFirstFrameID()){
    alert('Cannot delete the first or only remaining line in a script.');
    return false;
  }
  var editor = el('script_editor_frames');
  var frames = editor.getElementsByClassName('script_frame');
  var i = 0;
  for(var f in frames){
    if(frames[f].id == 'frame_'+x){
      editor.removeChild(frames[f]);
      showFrame(x-1);
      return;
    }
    i++;
  }  
}

function firstFrame(){
  var goToFrame = getFirstFrameID();
  showFrame(goToFrame);
  el('script_line_' + (goToFrame)).scrollIntoView();  
}

function prevFrame(x){
  var goToFrame = getPreviousFrameID(x);
  showFrame(goToFrame);
  el('script_line_' + (goToFrame)).scrollIntoView();
}

function nextFrame(x){
  var goToFrame = getNextFrameID(x);
  showFrame(goToFrame);
  el('script_line_' + (goToFrame)).scrollIntoView();
}

function lastFrame(){
  var goToFrame = getLastFrameID();
  showFrame(goToFrame);
  el('script_line_' + (goToFrame)).scrollIntoView();  
}

function goMenu(v){
  if(v == 'Table of Contents'){
    window.location = 'go/toc/';
  } else if(v == 'New Script File'){
    window.location = 'go/editor/script/new';
  } else if(v == 'Import CSV Script'){
    window.location = 'go/import';
  } else if(v != ''){
    window.location = 'go/editor/script/' + v;
  }
  
}

function editTitle(x,v){
  el('script_' + x + '_title').value = v;
}

function editText(x,v){
  el('script_' + x + '_text').value = v;
}

function curLink(type,link){
  
  switch(type){
    case 'background':
      var dir = 'bkgnd';
    break;
    case 'layers':
      var dir = 'layer';
    break;
    case 'music':
      var dir = 'bgm';
    break;
    case 'sfx':
      var dir = 'sfx';
    break;
    case 'video':
      var dir = 'vid';
    break;
    default:
      var dir = false;
    break;
  }
  
  if((link != '') && (link != 'clear') && (dir !== false)){
    return "<a href=\"../assets/" + dir + '/' + link + "\" target=\"_blank\" >" + link + "</a>\n";
  } else {
    return link;
  }
  
}

function highlightScriptLine(x){
  
  var lines = document.getElementById('script_lines').getElementsByClassName('script_line');
  for(var line in lines){
    if(el('script_line_' + line).className){
      var note_mark = (el('script_line_' + line).className.indexOf('note') != -1) ? ' note' : '';
      if(line == x){
        el('script_line_' + line).className = 'script_line hl' + note_mark;
      } else {
        el('script_line_' + line).className = 'script_line' + note_mark;
      }
    }
  }
  
}







/***************************************************
      FUNCTIONS RELATED TO ID DISCOVERY
***************************************************/

/*
* Core functions for finding element IDs
*/

function getPreviousID(container,classname,x){
  var items = document.getElementById(container).getElementsByClassName(classname);
  var found = false;
  var c = false;
  for(f in items){
    if((items[f].id) && (x == items[f].id.split('_')[1])){      
      c = (c === false) ? f : c;
      return parseInt(items[c].id.split('_')[1]);
    }
    c = f;
  }
  if(!found) return 0;
  return parseInt(items[c].id.split('_')[1]);
}

function getNextID(container,classname,x){
  var items = document.getElementById(container).getElementsByClassName(classname);
  var found = false;
  var c = false;
  for(f in items){
    if(items[f].id){
      if(found){
        return parseInt(items[f].id.split('_')[1]);
      }
      if((items[f].id) && (x == items[f].id.split('_')[1])){
        c = f;
        found = true;
      }
    }
  }
  if(!found) return 0;
  return parseInt(items[c].id.split('_')[1]);
}

function getFirstID(container,classname){
  var items = document.getElementById(container).getElementsByClassName(classname);
  for(f in items){
    if(items[f].id){
      return parseInt(items[f].id.split('_')[1]);
    }
  }
  return 0;
}

function getLastID(container,classname){
  var items = document.getElementById(container).getElementsByClassName(classname);
  var c = false;
  for(f in items){
    if(items[f].id){
      c = f;
    }
  }
  if(c === false) return 0;
  return parseInt(items[c].id.split('_')[1]);
}

function getNewID(container,classname){
  var items = document.getElementById(container).getElementsByClassName(classname);
  var max = 0;
  var c = 0;
  for(f in items){
    if(items[f].id){
      c = parseInt(items[f].id.split('_')[1]);
      max = (c > max) ? c : max;
    }
  }
  return max + 1;
}

function getPosForID(container,classname,x){
  var items = document.getElementById(container).getElementsByClassName(classname);
  for(f in items){
    if(items[f].id){
      if((items[f].id) && (x == items[f].id.split('_')[1])){
        return f;
      }
    }
  }
  return 0;
}

/*
* Functions to find frame IDs
*/

function getPreviousFrameID(x){
  return getPreviousID('script_editor_frames','script_frame',x);
}

function getNextFrameID(x){
  return getNextID('script_editor_frames','script_frame',x);
}

function getFirstFrameID(){
  return getFirstID('script_editor_frames','script_frame');
}

function getLastFrameID(){
  return getLastID('script_editor_frames','script_frame');
}

function getNewFrameID(){
  return getNewID('script_editor_frames','script_frame');
}

function getPosForFrameID(x){
  return getPosForID('script_editor_frames','script_frame',x);
}

/*
* Functions to find chapter IDs
*/

function getPreviousChapterID(x){
  return getPreviousID('toc_editor','chapter',x);
}

function getNextChapterID(x){
  return getNextID('toc_editor','chapter',x);
}

function getFirstChapterID(){
  return getFirstID('toc_editor','chapter');
}

function getLastChapterID(){
  return getLastID('toc_editor','chapter');
}

function getNewChapterID(){
  return getNewID('toc_editor','chapter');
}

function getPosForChapterID(x){
  return getPosForID('toc_editor','chapter',x);
}

/*
* Functions to find scenes IDs
*/

function getPreviousSceneID(chapter,x){
  return getPreviousID('chapter_' + chapter + '_scenes','scene',x);
}

function getNextSceneID(chapter,x){
  return getNextID('chapter_' + chapter + '_scenes','scene',x);
}

function getFirstSceneID(chapter){
  return getFirstID('chapter_' + chapter + '_scenes','scene');
}

function getLastSceneID(chapter){
  return getLastID('chapter_' + chapter + '_scenes','scene');
}

function getNewSceneID(chapter){
  return getNewID('chapter_' + chapter + '_scenes','scene');
}

function getPosForSceneID(chapter,x){
  return getPosForID('chapter_' + chapter + '_scenes','scene',x);
}





/***********************************************************************/

function moveChapter(direction,x){
  
  var editor = el('toc_editor');
  var items = editor.getElementsByClassName('chapter');
  
  switch(direction){
    
    case 'up':
    
      var swap_item = items[getPosForChapterID(x)];
      var prev_item = items[getPosForChapterID(getPreviousChapterID(x))];
      editor.insertBefore(swap_item,prev_item);      
    
    break;
    
    case 'down':
    default:
    
      var swap_item = items[getPosForChapterID(x)];
      var next_item = items[getPosForChapterID(getNextChapterID(x))];
      editor.insertBefore(next_item,swap_item);      
    
    break;
    
  }  
  
}

function addChapter(x){
  var editor = el('toc_editor');
  var chapters = editor.getElementsByClassName('chapter');
  var new_chapter = el('toc_editor_template').getElementsByTagName('tbody')[0];
  var new_chapter_id = getNewChapterID();
  var next_chapter_id = getNextChapterID(x);  
  var newHTML = new_chapter.outerHTML.toString();
  var regex = /TOCTEMPLATE/gi;
  newHTML = newHTML.replace(regex,(new_chapter_id));
  regex = /TOCSCENETEMPLATE/gi;
  newHTML = newHTML.replace(regex,(0));
  var new_chapter_element = document.createElement("table");
  var new_pos = getPosForChapterID(next_chapter_id);
  if((chapters[new_pos]) && (x != next_chapter_id)){
    editor.insertBefore(new_chapter_element,chapters[new_pos]);
  } else {
    editor.appendChild(new_chapter_element);
  }  
  new_chapter_element.outerHTML = newHTML;
}

function removeChapter(x){
  if(!confirm('You are about to delete an entire chapter.  Are you sure?')){
    return false;
  }
  if(!confirm('ARE YOU ABSOLUTELY SURE?!!')){ // Heh, heh.
    return false;
  }
  if(x == getFirstChapterID()){
    alert('Cannot delete the first or only remaining chapter.');
    return false;
  }
  var editor = el('toc_editor');
  var items = editor.getElementsByClassName('chapter');
  var i = 0;
  for(var f in items){
    if(items[f].id == 'chapter_'+x){
      editor.removeChild(items[f]);
      return;
    }
    i++;
  } 
}

function addScene(chapter_id,x){
  if((chapter_id == undefined) && (x == undefined)){
    chapter_id = getLastChapterID();
    x = getLastSceneID(chapter_id);
  }  
  var sc = el('chapter_' + chapter_id + '_scenes');
  var scenes = sc.getElementsByClassName('scene');
  var new_scene = el('toc_editor_template').getElementsByClassName('scene')[0];
  var new_scene_id = getNewSceneID(chapter_id);
  var next_scene_id = getNextSceneID(chapter_id,x);  
  var newHTML = new_scene.outerHTML.toString();
  var regex = /TOCSCENETEMPLATE/gi;
  newHTML = newHTML.replace(regex,(new_scene_id));
  var regex = /TOCTEMPLATE/gi;
  newHTML = newHTML.replace(regex,(chapter_id));
  var new_scene_element = document.createElement("table");
  var new_pos = getPosForSceneID(chapter_id,next_scene_id);
  if((scenes[new_pos]) && (x != next_scene_id)){
    sc.insertBefore(new_scene_element,scenes[new_pos]);
  } else {
    sc.appendChild(new_scene_element);
  }  
  new_scene_element.outerHTML = newHTML;
}

function removeScene(chapter_id,x){
  if(!confirm('You are about to remove a scene.  Are you sure?')){
    return false;
  }
  if(x == getFirstChapterID()){
    alert('Cannot delete the first or only remaining scene in a chapter.');
    return false;
  }
  var scenes = el('chapter_' + chapter_id + '_scenes');
  var items = scenes.getElementsByClassName('scene');
  var i = 0;
  for(var f in items){
    if(items[f].id == 'scene_'+x){
      scenes.removeChild(items[f]);
      return;
    }
    i++;
  }
}

function moveScene(direction,chapter_id,x){
  
  var scenes = el('chapter_' + chapter_id + '_scenes');
  var items = scenes.getElementsByClassName('scene');
  
  switch(direction){
    
    case 'up':
    
      var swap_item = items[getPosForSceneID(chapter_id,x)];
      var prev_item = items[getPosForSceneID(chapter_id,getPreviousSceneID(chapter_id,x))];
      scenes.insertBefore(swap_item,prev_item);      
    
    break;
    
    case 'down':
    default:
    
      var swap_item = items[getPosForSceneID(chapter_id,x)];
      var next_item = items[getPosForSceneID(chapter_id,getNextSceneID(chapter_id,x))];
      scenes.insertBefore(next_item,swap_item);      
    
    break;
    
  }  
  
}

function deleteScript(){
  return confirm('Are you ABSOLUTELY SURE you want to delete this script file?  Make sure you have backups.');
}

/************************************************/

function togglePreview(){
  var o = el('preview');
  o.className = (o.className == 'preview_hide') ? 'preview_show' : 'preview_hide';
}

function saveAs(){
  var cur_file_name = el('script_file_name').value;
  var save_file_name = prompt('File name:',cur_file_name);
  if(save_file_name == null){
    return false;
  } else if(save_file_name == ''){
    alert('You must specify a file name.');
    return false;
  } else if(save_file_name.indexOf('.json') == -1){
    alert('Invalid file name.  Must have a .json extension.');
    return false;
  } else {
    el('script_file_name').value = save_file_name;
    return true;
  }
  return false;
}

function saveScript(){
  var fn = el('script_file_name').value;
  if(fn == '') return saveAs();
  return true;
}

/************************************************/

// Gets the original position for a layer.
function getOriginalPosLayer(n){
  switch(n){
    case 1:
      return {'x':-30,'y':0};
    break;
    case 3:
      return {'x':20,'y':0};
    break;
    case 4:
      return {'x':40,'y':0};
    break;
    default:
      return {'x':0,'y':0};
    break;
  }
}

// Applies custom positioning to a layer.
function posLayer(n,x=0,y=0){
  var o = document.getElementById('preview_layer_' + n);
  o.style.setProperty('left',x + '%');
  o.style.setProperty('top',y + '%');
}

// Resets custom positioning for all layers.
function resetPosLayers(){
  for(var i=1;i<=6;i++){
    var o =  document.getElementById('preview_layer_' + i);
    var p = getOriginalPosLayer(i);
    o.style.setProperty('left',p.x + '%');
    o.style.setProperty('top',p.y + '%');
  }  
}

function updatePreview(c){
  
  // Background
  if((c.background != '') && (c.background != 'clear')){
    el('preview').style.setProperty('background-image',"url('../assets/bkgnd/" + c.background + "')");
  } else {
    el('preview').style.setProperty('background-image','none');
  }
  
  // Video placeholder
  if((c.video != '') && (c.video != 'clear')){
    el('preview_video').style.display = 'block';
  } else {
    el('preview_video').style.display = 'none';
  }
  
  // Title card
  //if((c.card != '') && (c.card != 'clear')){
  if(el('script_' + current_frame + '_card').value != ''){
    el('preview_card').style.display = 'block';
    el('preview_card').innerHTML = el('script_' + current_frame + '_card').value;
  } else {
    el('preview_card').style.display = 'none';
  }
  
  // Credits scroll
  //if((c.scroll != '') && (c.scroll != 'clear')){
  if(el('script_' + current_frame + '_scroll').value != ''){
    el('preview_scroll').style.display = 'block';
    el('preview_scroll').innerHTML = el('script_' + current_frame + '_scroll').value;
  } else {
    el('preview_scroll').style.display = 'none';
  }
  
  // Default layer positions.
  resetPosLayers();
  
  // Layer images.
  for(var i in c.layers){
    //var k = parseInt(i)+1;
    var k = parseInt(i);
    if((c.layers[i] != '') && (c.layers[i] != 'clear') && el('preview_layer_' + k)){
      el('preview_layer_' + k).style.display = 'block';
      el('preview_layer_' + k).style.setProperty('background-image',"url('../assets/layer/" + c.layers[i] + "')");
    } else if(el('preview_layer_' + k)){
      el('preview_layer_' + k).style.display = 'none';
      el('preview_layer_' + k).style.setProperty('background-image',"none");
    }
  }

  // Apply any positioning tweaks if they exist.
  for(var i in c.tweaks){
    var k = parseInt(i)+1;
    if(c.tweaks[i] != null){
      if((c.tweaks[i]['x'] != undefined) && (c.tweaks[i]['y'] != undefined)){
        posLayer(k,c.tweaks[i]['x'],c.tweaks[i]['y']);
      }
    }
  }
  
}

function togglePreview(){
  o = el('preview');
  o.className = (o.className == 'preview_show') ? 'preview_hide' : 'preview_show';
}












