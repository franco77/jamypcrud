$(document).ready(function(){
  filtros_listado($(".tabla_flexible").attr("tabla"),"id");
  carga_tabla_inicial();
  localStorage.setItem("ultimaFunc","carga_tabla_inicial();");
  $(".tabla_flexible").on("click",".elimina-registro",function(){eliminar(this);});
  $(".tabla_flexible").on("click",".editar-registro",function(){frmeditar(this);});
  $(".modal").on("click",".btnCerrar",function(){cerrar();});
  $("#formularios").on("click","#btnGuardar",function(){guardar();});
  $("#formularios").on("click","#btnEditar",function(){editar();});
  $(".modal").on("change",".upload",function(){subir(this);});
  $("#nuevo").click(function(){nuevo();});
  $("#buscar").click(function(){buscar();});
  $("#exportar").click(function(){$("#linkcsv").fadeIn(900);});
  $("#linkcsv").click(function(){$(this).fadeOut(900);});
  $("#borrar").click(function(){eliminar_lote();});
  $("table.tabla_flexible").on("click","th.ordenapor",function(){ordenar(this);});
  $("#siguiente").click(function(){siguiente();});
  $("#anterior").click(function(){anterior();});
  $("#primeros").click(function(){primeros();});
  $("#ultimos").click(function(){ultimos();});
  $("#todos").click(function(){todos();});
});
