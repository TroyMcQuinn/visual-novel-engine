var wait_state = {'text':false,'music':false,'sfx':false,'video':false};

var cur_task = '';
var last_key = '';
var debug_view = false;

var chapters = false;
var scene = false;

var cur_chapter = false; // Position in chapter list
var cur_scene = false; // Position in scene list
var position = 0; // Position in script

var music = false;
var sfx = false;

var music_volume = .5;
var sfx_volume = .7;
var msg_speed = 20;

var is_fullscreen = false;

var scrollInterval = false;

// Preloads all assets in the current script.
function preload(){
  
  var preloads = document.getElementById('preloads');
  
  var o = '';
  var p = 0;
  
  for(var i in scene){
    frame = scene[i];
    for(var k in frame){
      if((frame[k] != 'clear') && (frame[k] != '')){

        switch(k){
          
          case 'background':
            o += "<img id=\"preload_item_" + p + "\" src=\"assets/bkgnd/" + frame[k] + "\" alt=\"preload\" />\n";    
            p++;
          break;
          
          case 'layer1':
          case 'layer2':
          case 'layer3':
          case 'layer4':
          case 'layer5':
          case 'layer6':
            o += "<img id=\"preload_item_" + p + "\" src=\"assets/layer/" + frame[k] + "\" alt=\"preload\" />\n";
            p++;
          break;
          
          case 'music':
            o += "<audio id=\"preload_item_" + p + "\" preload=\"auto\" ><source src=\"assets/bgm/" + frame[k] + "\" /></audio>\n";
            p++;
          break;
          
          case 'sfx':
            o += "<audio id=\"preload_item_" + p + "\" preload=\"auto\" ><source src=\"assets/sfx/" + frame[k] + "\" /></audio>\n";
            p++;
          break;
          
          case 'video':
            o += "<video id=\"preload_item_" + p + "\" preload=\"auto\" ><source src=\"assets/vid/" + frame[k] + "\" /></video>\n";
            p++;
          break;
          
        }
        
      }
    }
  }
  
  preloads.innerHTML = o;
  
}

// Get and set wait state
function waitState(t,s){
  if((typeof t !== 'undefined') && (typeof s !== 'undefined')){
    wait_state[t] = s;
  }
  return !((wait_state.text === false) && (wait_state.music === false) && (wait_state.sfx === false) && (wait_state.video === false));
}

// Parses string after "#" in URL for a specified parameter.
function getURLHashVar(key){
  var url = window.location.toString();
  var hashIndex = url.indexOf('#') + 1;
  if(hashIndex == -1) return false;
  var query = url.substring(hashIndex);
  var parameters = query.split('&');
  for(var i in parameters){
    var parm = parameters[i].split('=');
    if((parm[1]) && (parm[0] == key)){
      return parm[1];
    }
  }
  return false;
}

// AJAX (HttpRequest) Object.
function AJAX(){
  if (window.XMLHttpRequest){
    return new XMLHttpRequest();
  } else if (window.ActiveXObject){
    return new ActiveXObject("Msxml2.XMLHTTP");
  }
}

// Loads a script.
function getScript(script){  
  cur_task = 'getscript';
  if(waitState()) return false;
	var ajax = AJAX();
	ajax.onreadystatechange = function(){
		if((ajax.readyState == 4) && (ajax.status == 200)){
      cur_task = 'getscript readystate';
      scene = JSON.parse(ajax.responseText);
      clearAll();
      position = 0;
      preload();
      playScript(0);
      saveProgress();
		}
	};	
	ajax.open('GET','./script/' + script + '.json',true);
  ajax.send();
}

// Loads navigation / chapter / scene data.
function getChapters(){
  cur_task = 'getchapters';
  var ajax = AJAX();
	ajax.onreadystatechange = function(){
		if((ajax.readyState == 4) && (ajax.status == 200)){
      cur_task = 'getchapters readystate';
      chapters = JSON.parse(ajax.responseText);
      buildNavigationMenu();
      initLoad();
		}    
	};
	ajax.open('GET','./chapters.json',true);
  ajax.send();  
}

// Navigates to a specified chapter and scene script.
function navigateToScript(nav_code){
  nav_code = nav_code.split('-');
  if(!nav_code[1]) return false;
  cur_chapter = nav_code[0];
  cur_scene = nav_code[1];
  script = chapters[cur_chapter].scenes[cur_scene].file;
  getScript(script);  
}

// Builds the navigation menu.
function buildNavigationMenu(){
  if(chapters == false) return false;
  var o = '';
  for(var i in chapters){
    var scenes = chapters[i].scenes;
    o += "<optgroup label=\"" + chapters[i].label + "\">\n";
    for(var k in scenes){
      o += "<option value=\"" + i + '-' + k + "\">" + scenes[k].name + "</option>\n";
    }
    o += "</optgroup>\n";
  }
  document.getElementById('chapter_navigation_menu').innerHTML = o;
}

// Function to search a <select> menu for a value and return its index.
function searchMenu(menu,value){
  for(var i in menu.options){
    if(menu.options[i].value == value){
      return i;
    }
  }
  return false;  
}

// Sets the current option in the navigation pulldown menu to the current scene.
function setNavigationMenu(){
  var menu = document.getElementById('chapter_navigation_menu');
  var index = searchMenu(menu,cur_chapter + '-' + cur_scene);
  menu.selectedIndex = index;
}

// Executes the script at the current position.
function playScript(pos){
  
  cur_task = 'playscript';
  
  if(waitState()) return false;
  
  // Hide the menu if it's up.
  if(document.getElementById('menu').className == 'menu_show'){
    toggleMenu();
  }
  
  // Determine which navigation buttons should be displayed.
  setUpNavButtons();
  
  // Update the navigation dropdown menu to select the current scene.
  setNavigationMenu();
  
  // Update the URL to reflect the current chapter and scene.
  updateURL();
  
  if(scene[pos]){
    
    var frame = scene[pos];
    var layers = 6; // Number of available layers.
    
    // Force fade in if on first frame.
    if(pos == 0){
      fadeIn();
    }
    
    // Handle Backgrounds
    if(frame.background){
      if(frame.background != 'clear'){
        loadBackground(frame.background);
      } else {
        clearBackground();
      }
    }
    
    // Handle layers (characters, etc.)
    for(var i=1; i<=layers; i++){
      if(frame['layer' + i]){
        if(frame['layer' + i] != 'clear'){
          loadLayer(i,frame['layer' + i]);
        } else {
          clearLayer(i);
        }
      }
    }
    
    // Handle custom positioning for layers.
    if(frame.tweaks){
      for(var p in frame.tweaks){
        if((frame.tweaks[p].x != 'undefined') && (frame.tweaks[p].y != 'undefined')){
          posLayer(p,frame.tweaks[p].x,frame.tweaks[p].y);
        }
      }
    }
    
    // Handle title cards
    if(frame.card){
      if(frame.card == 'clear'){
        clearCard();
      } else {
        showCard(frame.card);
      }      
    } else {
      clearCard();
    }
    
    // Handle credits / information scroller
    if(frame.scroll){
      if(frame.scroll == 'clear'){
        clearScroller();
      } else {
        startScroller(frame.scroll); // Expects simple array of HTML lines.
      }      
    } else {
      clearScroller();
    }
    
    // Handle fade in and out
    if(frame.fade){
      fade(frame.fade);
    }
    
    // Handle dialog text
    if((frame.text) && (frame.text != '')){
      
      if(document.getElementById('dialogue').className == 'dialogue_off'){
        toggleDialogBox();
      }
      
      if(frame.title){
        typeDialog(frame.title,frame.text);
      } else {
        typeDialog('',frame.text);
      }
      
    } else if(document.getElementById('dialogue').className == 'dialogue_on'){
      toggleDialogBox();
    }
    
    // Handle music
    if(frame.music){
      if(frame.music != 'clear'){
        playMusic(frame.music);
      } else {
        stopMusic();
      }
    }
    
    // Handle sound effects
    if(frame.sfx){
      if(frame.sfx != 'clear'){
        playSFX(frame.sfx);
      } else {
        stopSFX();
      }
    }
    
    // Handle video
    if(frame.video){
      if(frame.video != 'clear'){
        loadVideo(frame.video);
      } else {
        clearVideo();
      }
    }
    
    // Handle clickable link assignment
    if(frame.link){
      if(frame.link != 'clear'){
        setClickableLink(frame.link);
      } else {
        unsetClickableLink();
      }
    }
    
  } else {
    
    return false;
  }
  
}

// Gets the original position for a layer.
function getOriginalPosLayer(n){
  cur_task = 'getoriginalposlayer';
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
  cur_task = 'poslayer';
  var o = document.getElementById(n);
  o.style.setProperty('left',x + '%');
  o.style.setProperty('top',y + '%');
}

// Resets custom positioning for all layers.
function resetPosLayers(){
  cur_task = 'resetposlayers';
  for(var i=1;i<=6;i++){
    var o =  document.getElementById('layer' + i);
    var p = getOriginalPosLayer(i);
    o.style.setProperty('left',p.x + '%');
    o.style.setProperty('top',p.y + '%');
  }  
}

// Fade in and out
function fade(color='#000000'){
  cur_task = 'fade';
  var o = document.getElementById('fade_layer');
  o.style.setProperty('background-color',color);
  o.className = (o.className == 'layer') ? 'layer_hide' : 'layer';
}

function fadeIn(){
  cur_task = 'fadein';
  var o = document.getElementById('fade_layer');
  o.className = 'layer_hide';
}

function fadeOut(){
  cur_task = 'fadein';
  var o = document.getElementById('fade_layer');
  o.style.setProperty('background-color','black');
  o.className = 'layer';
}

// Determines which navigation buttons should appear based on certain criteria.
function setUpNavButtons(){
  
  cur_task = 'setupnavbuttons';
  
  var first = document.getElementById('first');
  var prev = document.getElementById('prev');
  var back = document.getElementById('back');
  var next = document.getElementById('next');
  var skip = document.getElementById('skip');
  var last = document.getElementById('last');
  
  next.className = 'nav_button'; // Default unless conditions specify otherwise.
  
  if((cur_chapter == 0) && (cur_scene == 0)){
    first.className = 'nav_button_disabled';
    prev.className = 'nav_button_disabled';
  } else {
    first.className = 'nav_button';
    prev.className = 'nav_button';
  }
  
  if((cur_chapter == chapters.length - 1) && (cur_scene == chapters[cur_chapter].scenes.length - 1)){
    skip.className = 'nav_button_disabled';
    last.className = 'nav_button_disabled';
    if(position == scene.length - 1){
      next.className = 'nav_button_disabled';      
    } else {
      next.className = 'nav_button';
    }
  } else {
    skip.className = 'nav_button';
    last.className = 'nav_button';
  }  
  
}

// Allow keyboard controls
document.addEventListener("keydown", function(event) {  
  
  last_key = event.keyCode; // Keep track of which key is pressed for debugging purposes.
  
  // Allow enter key for full screen toggle in menu screen.
  if (event.keyCode == 13) {
    event.preventDefault(); // Prevent default behavior
    toggleFullScreen();
  }
  
  // Disable keyboard input while in the menu screen.
  var menu = document.getElementById('menu');
  if(menu.className == 'menu_show') return false;
  
  // Disallow if in wait state.
  if(waitState()) return false;
  
  if (event.keyCode == 32) {
    event.preventDefault(); // Prevent default behavior
    next();
  }
  
  // Debug view mode
  if (event.keyCode == 68) {
    
    if(debug_view){
      var debug_bar = document.getElementById('debug_view');
      if(debug_bar.style.display == 'none'){
        debug_bar.style.display = 'block';
      } else {
        debug_bar.style.display = 'none';
      }      
    } else {
      debugView();
      debug_view = true;
    } 
    
  }

  // Arrow keys  
  if (event.keyCode == 37) {
    back();
  }
  
  if (event.keyCode == 38) {
    skip();
  }
  
  if (event.keyCode == 39) {
    next();
  }
  
  if (event.keyCode == 40) {
    prev();
  }
  
  return false;
  
});

function start(){  
  cur_task = 'start';
  // Load the table of contents.
  getChapters();
}

// Loads the inital scene.
function initLoad(){  

  cur_task = 'initload';

  // Check to see if the URL contains parameters for the chapter and scene.
  var url_chapter = getURLHashVar('chapter');
  var url_scene = getURLHashVar('scene');
  var url_script_file = getURLHashVar('script');
  
  if(url_chapter !== false){
    
    url_scene = (url_scene !== false) ? (url_scene - 1) : 0;
    cur_chapter = url_chapter;
    cur_scene = url_scene;
    
  } else { // Load saved progress or the first scene specified in the table of contents.
  
    cur_chapter = 0;
    cur_scene = 0;    
    
    // Use saved progress if available.
    loadProgress();
    
  }
  
  if(url_script_file === false){
    script = chapters[cur_chapter].scenes[cur_scene].file;
  } else {
    script = url_script_file;
  }
  
  getScript(script);
  
}

// Updates the current URL to reflect the chapter and scene.
function updateURL(){
  var url = window.location.toString().split('#');
  var link_button = document.getElementById('link_button');  
  if(getURLHashVar('script') !== false){
    link_button.href = url[0] + '#script=' + getURLHashVar('script');
  } else if((cur_chapter == 0) && (cur_scene == 0)){
    link_button.href = url[0];
  } else {    
    link_button.href = url[0] + '#chapter=' + cur_chapter + '&scene=' + (parseInt(cur_scene) + 1);
  }
}

// Advances to the next frame of the scene.
function next(){
  cur_task = 'next';
  if (waitState()) return false;
  if(position < scene.length - 1){
    position++;
    playScript(position);
  } else {
    skip();
  }
}

// Returns to the beginning of the scene.
function back(){
  cur_task = 'back';
  if(waitState()) return false;
  position = 0;
  playScript(position);
}

// Jumps back to the previous scene.
function prev(){  
  cur_task = 'prev';
  if(waitState()) return false;
  if(cur_scene > 0){
    cur_scene--;
  } else if((cur_chapter > 0) && (cur_scene == 0)){
    cur_chapter--;
    cur_scene = chapters[cur_chapter].scenes.length - 1;
  } else {
    return false;
  }
  script = chapters[cur_chapter].scenes[cur_scene].file;
  getScript(script);
}

// Skips to the next scene.
function skip(){
  cur_task = 'skip';
  if(waitState()) return false;
  if(cur_scene < chapters[cur_chapter].scenes.length - 1){
    cur_scene++;    
  } else if((cur_scene == chapters[cur_chapter].scenes.length - 1) && (cur_chapter < chapters.length - 1)){
    cur_scene = 0;
    cur_chapter++;    
  } else {
    return false;
  }
  script = chapters[cur_chapter].scenes[cur_scene].file;
  getScript(script);
}

// Jumps back to the beginning.
function first(){
  cur_task = 'first';
  if(waitState()) return false;
  if((cur_scene > 0) || (cur_chapter > 0)){
    cur_chapter = 0;
    cur_scene = 0;
    script = chapters[cur_chapter].scenes[cur_scene].file;
    getScript(script);
  }
}

// Skips to the most recent scene.
function last(){
  cur_task = 'last';
  if(waitState()) return false;
  if((cur_scene < chapters[cur_chapter].scenes.length - 1) || (cur_chapter < chapters.length - 1)){
    cur_chapter = chapters.length - 1;
    cur_scene = chapters[cur_chapter].scenes.length - 1;
    script = chapters[cur_chapter].scenes[cur_scene].file;
    getScript(script);
  }
}

// Toggles the menu.
function toggleMenu(){
  cur_task = 'togglemenu';
  var menu = document.getElementById('menu');
  var buttons = document.getElementById('nav_buttons');
  if(menu.className == 'menu_hide'){
    menu.className = 'menu_show';
    buttons.className = 'menu_hide';
  } else {
    menu.className = 'menu_hide';
    buttons.className = 'menu_show';
  }  
}

function toggleFullScreen(){
  cur_task = 'togglefullscreen';
  var docElm = document.documentElement;
  if(!is_fullscreen){
    if (docElm.requestFullscreen) {
      docElm.requestFullscreen();
    } else if (docElm.mozRequestFullScreen) {
      docElm.mozRequestFullScreen();
    } else if (docElm.webkitRequestFullScreen) {
      docElm.webkitRequestFullScreen();
    } else if (docElm.msRequestFullscreen) {
      docElm.msRequestFullscreen();
    }
    is_fullscreen = true;
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
    is_fullscreen = false;
  }
}

function toggleDialogBox(){  
  cur_task = 'toggledialogbox';
  var dialog_box = document.getElementById('dialogue');  
  if(dialog_box.className == 'dialogue_off'){
    dialog_box.className = 'dialogue_on';
  } else {
    dialog_box.className = 'dialogue_off';
  }
}

function test(){
  if(waitState()) return;

  
}

function debugView(){
  
  if(debug_view) return false; // Already activated?
  
  document.getElementById('debug_view').style.setProperty('display','block');
  
  var speed = 10;
  
  var interval = setInterval(updateDebugView,speed);
  
  function updateDebugView(){
    var o = '';
    o += 'chapter ' + cur_chapter;
    o += ' scene ' + (cur_scene + 1);
    o += ' frame ' + (position + 1) + ' of ' + scene.length;
    o += (waitState() ? ' wait ' : '');
    o += ' ' + cur_task;
    o += ' key: ' + last_key + ' ';
    
    document.getElementById('debug_view').innerHTML = o;
    
  }
  
}

function typeDialog(title,txt){
  
  cur_task = 'typedialog';
  
  waitState('text',true);
  
  var dialog_title = document.getElementById('dialogue_title');
  var dialog_text = document.getElementById('dialogue_text');
  
  if(title == ''){
    dialog_title.style.display = 'none';
    dialog_text.style.setProperty("top","5%");
  } else {
    dialog_title.style.display = 'block';
    dialog_text.style.setProperty("top","-5%");
  }
  
  dialog_title.innerHTML = title;
  
  dialog_text.innerHTML = '';
  
  var len = txt.length;
  var cur = 0;
  var speed = msg_speed;
  var delay = msg_speed * 2;
  var wait = 0;
  var interval = setInterval(typeLetter,speed);
  
  function typeLetter(){
    if(wait < delay){
      wait++;
      return;
    }
    if(cur < len){
      var cur_text = dialog_text.innerHTML;
      cur_text += txt.substring(cur,cur+1);
      dialog_text.innerHTML = cur_text;
      cur++;
    } else {
      clearInterval(interval);
      waitState('text',false);
    }
  }  
  
}

function clearAll(){
  clearBackground();
  for(var i=1; i<=6; i++){
    clearLayer(i);
  }
  fadeIn();
  setTimeout(function(){resetPosLayers()},1000);
  clearVideo();
  stopMusic();
  stopSFX();
}

function loadBackground(url){
  cur_task = 'loadbackground';
  var stage = document.getElementById('stage');
  url = './assets/bkgnd/' + url;
  stage.style.setProperty("background-image",'url(\'' + url + '\')');
}

function clearBackground(){
  cur_task = 'clearbackground';
  var stage = document.getElementById('stage');
  stage.style.setProperty("background-image",'none');
}

function loadLayer(layer,url){
  cur_task = 'loadlayer';
  var layer = document.getElementById('layer' + layer);
  url = './assets/layer/' + url;
  layer.style.setProperty("background-image",'url(\'' + url + '\')');
  layer.className = 'layer';
}

function clearLayer(layer){
  cur_task = 'clearlayer';
  var layer = document.getElementById('layer' + layer);
  //layer.style.setProperty("background-image",'none');
  layer.className = 'layer_hide';
}

function loadVideo(url){
  clearVideo();
  cur_task = 'loadvideo';
  waitState('video',true);
  var layer = document.getElementById('video_layer');
  var video = document.getElementById('video');
  url = './assets/vid/' + url;
  video.src = url;
  video.load();
  video_promise = video.play();
  video_promise.then(function(){
    cur_task = 'loadvideo playing';
    waitState('video',false);
  }).catch(function(error){});
  layer.className = 'layer';
}

function clearVideo(){
  cur_task = 'clearvideo';
  var layer = document.getElementById('video_layer');
  var video = document.getElementById('video');
  video.pause();
  layer.className = 'layer_hide';
}

function playMusic(url){
  stopMusic();
  cur_task = 'playmusic';
  waitState('music',true);
  try{
    music = new Audio('./assets/bgm/' + url);
    music.volume = music_volume;
    music.loop = true;
    music_promise = music.play();
    music_promise.then(function(){
      cur_task = 'playmusic playing';
      waitState('music',false);
    }).catch(function(error){});
  } catch(e){
    waitState('music',false);
  }
}

function stopMusic(){
  cur_task = 'stopmusic';
  if(music == false) return false;
  music.pause();
  music = false;
}

function playSFX(url){
  stopSFX();
  cur_task = 'playsfx';
  waitState('sfx',true);
  try{
    sfx = new Audio('./assets/sfx/' + url);
    sfx.volume = sfx_volume;
    sfx_promise = sfx.play();
    sfx_promise.then(function(){
      cur_task = 'playsfx playing';
      waitState('sfx',false);
    }).catch(function(error){});
  } catch(e){
    waitState('sfx',false);
  }
}

function stopSFX(){
  cur_task = 'stopsfx';
  if(sfx == false) return false;
  sfx.pause();
  sfx = false;
}

function setMusicVolume(x){
  cur_task = 'setmusicvolume';
  x = x / 10;
  music_volume = x;
  music.volume = x;  
  saveProgress();
}

function setSFXVolume(x){
  cur_task = 'setsfxvolume';
  x = x / 10;
  sfx_volume = x;
  sfx.volume = x;  
  saveProgress();
}

function setMessageSpeed(x){
  cur_task = 'setmessagespeed';
  msg_speed = 50 - x;
  saveProgress();
}

function showCard(x){
  var o = document.getElementById('card');
  o.innerHTML = x;
  o.className = 'layer_show';
}

function clearCard(){
  var o = document.getElementById('card');
  o.innerHTML = '';
  o.className = 'layer_hide';
}

function startScroller(x){ // x should be an array of HTML lines.
  cur_task = 'startscroller';
  var o = document.getElementById('scroller');
  var t = document.getElementById('scrolltext');
  scrollerPos = o.clientHeight;
  var h = '';
  for(var l in x){
    h += x[l];
  }
  t.style.top = scrollerPos + 'px';
  t.innerHTML = h;
  o.className = 'layer_show';
  scrollInterval = setInterval(runScroller, 24);
}

function clearScroller(){
  cur_task = 'clearscroller';
  var o = document.getElementById('scroller');
  var t = document.getElementById('scrolltext');
  t.innerHTML = '';
  t.style.top = '0px';
  o.className = 'layer_hide';
  clearInterval(scrollInterval);
}

function runScroller(){
  var t = document.getElementById('scrolltext');
  scrollerPos--;
  t.style.top = scrollerPos + 'px';
}

function saveCookie(data,years){
  var d = new Date();
  var c = '';
  var s = '';
  var days = years * 365;
  d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
  var expiration = d.toUTCString();
  for(var property in data){
    if(data.hasOwnProperty(property)){
      s += property + ':' + data[property] + ',';
    }    
  }
  c += 'data=' + s + ';';
  c += 'expires=' + expiration + ';';
  c += 'path=/';
  document.cookie = c;
}

function getCookie(){
  if(!document.cookie) return false;
  var cookie = document.cookie.toString().split(';')[0].split('=')[1].split(',');
  if(cookie[1]){
    var data = {};
    for(var i in cookie){
      if((cookie[i].split(':')[0] != '') && (cookie[i].split(':')[1])){
        data[cookie[i].split(':')[0]] = cookie[i].split(':')[1];
      }
    }
    return data;
  } else {
    return false;
  }
}

function saveProgress(){
  var mv = music_volume * 10;
  var sfxv = sfx_volume * 10;
  var saveData = {'chapter':cur_chapter,'scene':cur_scene,'mv':mv,'sfxv':sfxv,'msgspd':msg_speed};
  saveCookie(saveData,10);
}

function loadProgress(){
  var saveData = getCookie();
  if(saveData.chapter && saveData.scene){
    cur_chapter = saveData.chapter;
    cur_scene = saveData.scene;
    music_volume = saveData.mv / 10;
    sfx_volume = saveData.sfxv / 10;
    if(saveData.msgspd){
      msg_speed = saveData.msgspd;
    }
    document.getElementById('music_volume').value = saveData.mv;
    document.getElementById('sfx_volume').value = saveData.sfxv;
    document.getElementById('message_speed').value = 50 - msg_speed;
  }
}

function setClickableLink(x){
  var clickableLayer = document.getElementById('stage');
  clickableLayer.setAttribute('onclick',"window.open('" + x + "','_blank');");
  
}

function unsetClickableLink(){
  var clickableLayer = document.getElementById('stage');
  clickableLayer.removeAttribute('onclick');  
}

/*
window.onerror = function(msg, url, linenumber) {
    alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
    return true;
}
*/