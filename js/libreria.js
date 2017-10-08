var altura=screen.height;
var tabla_altura=(altura/100)*49;
$(".tablacustom").height(tabla_altura);
var busqueda1="";
var busqueda2="";
var esfecha=$("#fechas").is(":checked");
var posicion=0;
var dir="asc";
var columna="";
function filtros_listado(tabla,excluir){
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: tabla,accion:"rellenacombo",exc:excluir}
}).done(function(listado){
//console.log(listado);
localStorage.setItem("excluye",excluir);
$("#campo_filtro").html(listado);
$("#campo_filtro2").html(listado);
$("#tablatitulo").text(tabla);
}).fail(function(a,b,c){
console.log(c);
});
}

function carga_tabla_inicial(){
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"rellenartbl",exc:localStorage.getItem("excluye")}
}).done(function(dts){
console.log(dts);
console.log(localStorage.getItem("excluye"));
localStorage.removeItem("direccion_orden");
localStorage.removeItem("columna_ord");
$(".tabla_flexible").prepend(dts);
}).fail(function(fallo,x,y){
console.log(y);
});
localStorage.setItem("direccion_orden", "asc");
}
function eliminar(obj){
var campo_val=$(obj).attr("id-campo");
var id_val=$(obj).attr("id-registro");
var confirma=confirm("Realmente quiere eliminar este registro?");
  if(confirma===true){
  $.ajax({
  method: "POST",
  cache: false,
  url: "cfgdb.php",
  data: {accion:"eliminar-registro",tbl:$(".tabla_flexible").attr("tabla"),campo:campo_val ,id:id_val }
  }).done(function(r){
  $("#mensajealerta").html(r);
  $("#mensajealerta").fadeIn(900);
  $("#mensajealerta").delay(1500).fadeOut(900);
  $(".tabla_flexible").html("");
  ufunc=localStorage.getItem("ultimaFunc");
  eval(ufunc);
  }).fail(function(a,b,c){
  console.log(c);
  });
  }
}
function frmeditar(obj){
var id_val=$(obj).attr("id-registro");
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {accion:"editar-registro",tbl:$(".tabla_flexible").attr("tabla"),id:id_val }
}).done(function(dts){
console.log(dts);
$("#formularios").html(dts);
$("#formularios").fadeIn(600);
}).fail(function(a,b,c){
console.log(c);
});
}
function cerrar(){
var seguro=confirm("¡Perderá los cambios no guardados!Desea cerrar?");
  if(seguro===true){
  $(".modal").fadeOut(100);
  $(".modal").html("");
  }
}
function guardar(){
var error=0;
$(".obligatorio").each(function(idx,val){
  if($.trim($(this).val())=="" || ($(this).attr("type")=="file" && $(this).attr("saved-url")=="")){
  msj_error=$(this).attr("campo-nombre")+" es obligatorio";
  $(this).attr("placeholder",msj_error);
  $(this).css("border","1.5px solid #F00");
  error++;
  }else{
  $(this).removeAttr("placeholder");
  $(this).css("border","0px");
  }
});
  if(error==0){
  var datos='{';
  $(".campo").each(function(){
  if($(this).attr("type")=="radio" || $(this).attr("type")=="checkbox"){
  if($(this).is(":checked")){
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).val()+'",';
  }
  }else if($(this).attr("type")=="file"){
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).attr("saved-url")+'",';
  }else{
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).val()+'",';
  }
});
datos=datos.substring(0,datos.length-1);
datos+='}';
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {valores: datos,tbl:$(".tabla_flexible").attr("tabla"),accion:"guardar"}
}).done(function(resp){
console.log(resp);
$(".tabla_flexible").html("");
ufunc=localStorage.getItem("ultimaFunc");
eval(ufunc);
$("#formularios").fadeOut(300);
$("#formularios").html("");
$("#mensajealerta").html(resp);
$("#mensajealerta").fadeIn(300);
$("#mensajealerta").delay(2800).fadeOut(500);
}).fail(function(a,b,c){
console.log(c);
});
}
}
function editar(){
var error=0;
$(".obligatorio").each(function(idx,val){
  if($.trim($(this).val())=="" || ($(this).attr("type")=="file" && $(this).attr("saved-url")=="")){
  msj_error=$(this).attr("campo-nombre")+" es obligatorio";
  $(this).attr("placeholder",msj_error);
  $(this).css("border","1.5px solid #F00");
  error++;
  }else{
  $(this).removeAttr("placeholder");
  $(this).css("border","0px");
  }
});
if(error==0){
  var datos='{';
  $(".campo").each(function(){
  if($(this).attr("type")=="radio" || $(this).attr("type")=="checkbox"){
  if($(this).is(":checked")){
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).val()+'",';
  }
  }else if($(this).attr("type")=="file"){
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).attr("saved-url")+'",';
  }else if($(this).attr("type")=="hidden"){
  datos+='"'+$(this).attr("id")+'":"'+$(this).val()+'",';
  }else{
  datos+='"'+$(this).attr("campo-nombre")+'":"'+$(this).val()+'",';
  }
});
datos=datos.substring(0,datos.length-1);
datos+='}';
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {valores: datos,tbl:$(".tabla_flexible").attr("tabla"),accion:"editar"}
}).done(function(resp){
console.log(resp);
$(".tabla_flexible").html("");
ufunc=localStorage.getItem("ultimaFunc");
eval(ufunc);
$("#formularios").fadeOut(300);
$("#formularios").html("");
$("#mensajealerta").html(resp);
$("#mensajealerta").fadeIn(300);
$("#mensajealerta").delay(2800).fadeOut(500);
}).fail(function(a,b,c){
console.log(c);
});
}
}
function subir(obj){
var file_data=$(obj).prop('files')[0];
var formData = new FormData();
var id=$(obj).attr("id");
formData.append('archivo', file_data);
$.ajax({
url: 'cfgdb.php',
success: function(resp){
console.log(resp);
$("#"+id).attr("saved-url",resp);
},
error: function(f,g,h){
console.log(h);
},
dataType: 'text',
method: 'post',
data: formData,
cache: false,
contentType: false,
processData: false
});
}
function nuevo(){
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {accion:"nuevo-registro",tbl:$(".tabla_flexible").attr("tabla")}
}).done(function(dts){
$("#formularios").html(dts);
$("#formularios").fadeIn(600);
}).fail(function(a,b,c){
console.log(c);
});
}
function buscar(){
  if(($.trim($("#valor_filtro").val())=="" || $("#campo_filtro").val()=="") && ($.trim($("#valor_filtro2").val())=="" || $("#campo_filtro2").val()=="")){
  alert("Debe ingresar un campo y valor de búsqueda");
  return;
  }
  if($("#campo_filtro").val()!="" && $.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  localStorage.setItem("busca1",busqueda1);
  }
  if($("#campo_filtro2").val()!="" && $.trim($("#valor_filtro2").val())!=""){
  busqueda2=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  localStorage.setItem("busca2",busqueda2);
  }
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"filtrartbl",exc:localStorage.getItem("excluye"),offset:"0",busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha }
}).done(function(dts){
console.log(dts);
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
localStorage.setItem("ultimaFunc","buscar();");
}).fail(function(fallo,x,y){
console.log(y);
});
}
function eliminar_lote(){
var confirma=confirm("Realmente desea eliminar estos registros?");
  if(confirma){
  var cond1="";
  var cond2="";
  if($.trim($("#valor_filtro").val())!=""){
  cond1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  cond2=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
console.log(esfecha);
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl:$(".tabla_flexible").attr("tabla"),accion:"eliminar",condicion1: cond1,condicion2:cond2,es_fecha:esfecha }
}).done(function(resp){
console.log(resp);
  if(resp=="1"){
  $("#mensajealerta").fadeIn(800);
  $("#mensajealerta").delay(2500).fadeOut(900);
  $(".tabla_flexible").html("");
  ufunc=localStorage.getItem("ultimaFunc");
  eval(ufunc);
  }else{
  console.log(resp);
  }
});
}
}
function ordenar(obj){
var columna=$(obj).attr("id");
localStorage.setItem("columna_ord",columna);
var dir=localStorage.getItem("direccion_orden");
  if($.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
  if(dir=="asc"){
  localStorage.setItem("direccion_orden", "desc");
  }else{
  localStorage.setItem("direccion_orden", "asc");
  }
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"ordenatbl",criterio: columna,direccion:dir,exc:localStorage.getItem("excluye"),busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(dts){
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
localStorage.setItem("ultimaFunc","ordenar();");
}).fail(function(fallo,x,y){
console.log(x);
console.log(y);
});
}
function siguiente(){
posicion+=15;
console.log(posicion);
  if(typeof  localStorage.getItem("direccion_orden")!=='undefined'){
  dir=localStorage.getItem("direccion_orden");
  }
  if(typeof  localStorage.getItem("columna_ord")!=='undefined'){
  columna=localStorage.getItem("columna_ord");
  }
  if($.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"desplaza",offset:posicion,direccion: dir,criterio:columna,exc:localStorage.getItem("excluye"),busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(dts){
console.log(dts);
  if(dts=="0"){
  alert("No existen más registros hacia adelante");
  return;
  }
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
}).fail(function(fallo,x,y){
console.log(x);
console.log(y);
});
}
function anterior(){
posicion-=15;
console.log(posicion);
  if(posicion<0){
  alert("No existen más registros hacia atrás");
  return;
  }
  if(typeof  localStorage.getItem("direccion_orden")!=='undefined'){
  dir=localStorage.getItem("direccion_orden");
  }
  if(typeof  localStorage.getItem("columna_ord")!=='undefined'){
  columna=localStorage.getItem("columna_ord");
  }
  if($.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"desplaza",offset:posicion,direccion: dir,criterio:columna,exc:localStorage.getItem("excluye"),busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(dts){
if(dts!="0"){
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
}else{
return;
}
}).fail(function(fallo,x,y){
console.log(x);
console.log(y);
});
}
function primeros(){
posicion=0;
console.log(posicion);
  if(typeof  localStorage.getItem("direccion_orden")!=='undefined'){
  dir=localStorage.getItem("direccion_orden");
  }
  if(typeof  localStorage.getItem("columna_ord")!=='undefined'){
  columna=localStorage.getItem("columna_ord");
  }
  if($.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"desplaza",offset:posicion,direccion: dir,criterio:columna,exc:localStorage.getItem("excluye"),busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(dts){
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
}).fail(function(fallo,x,y){
console.log(x);
console.log(y);
});
}
function ultimos(){
  if(typeof  localStorage.getItem("direccion_orden")!=='undefined'){
  dir=localStorage.getItem("direccion_orden");
  }
  if(typeof  localStorage.getItem("columna_ord")!=='undefined'){
  columna=localStorage.getItem("columna_ord");
  }
  if($.trim($("#valor_filtro").val())!=""){
  busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
  busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
var cuantos=0;
$.ajax({
method: "POST",
cache: false,
url: "cfgdb.php",
async: false,
data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"calculatotal",busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(num){
  cuantos=num;
}).fail(function(a,e,i){
console.log(i);
});
console.log(cuantos);
  if(cuantos>15){
  posicion=cuantos-15;
  }else{
  alert("No hay más registros adelante");
  return;
  }
$.ajax({
  method: "POST",
  cache: false,
  url: "cfgdb.php",
  data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"desplaza",offset:posicion,direccion: dir,criterio:columna,exc:localStorage.getItem("excluye"),busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha}
}).done(function(dts){
$(".tabla_flexible").html("");
$(".tabla_flexible").prepend(dts);
}).fail(function(fallo,x,y){
console.log(x);
console.log(y);
});
}
function todos(){
  if(typeof  localStorage.getItem("direccion_orden")!=='undefined'){
    dir=localStorage.getItem("direccion_orden");
  }
  if(typeof  localStorage.getItem("columna_ord")!=='undefined'){
    columna=localStorage.getItem("columna_ord");
  }
  if($.trim($("#valor_filtro").val())!=""){
    busqueda1=$("#campo_filtro").val()+'|'+$("#valor_filtro").val();
  }
  if($.trim($("#valor_filtro2").val())!=""){
    busqueda1=$("#campo_filtro2").val()+'|'+$("#valor_filtro2").val();
  }
$.ajax({
    method: "POST",
    cache: false,
    url: "cfgdb.php",
    data: {tbl: $(".tabla_flexible").attr("tabla"),accion:"filtrartbl",exc:localStorage.getItem("excluye"),offset:"-2",busc1:busqueda1,busc2:busqueda2,es_fecha:esfecha }
}).done(function(dts){
  console.log(dts);
  $(".tabla_flexible").html("");
  $(".tabla_flexible").prepend(dts);
}).fail(function(fallo,x,y){
  console.log(y);
});
}
