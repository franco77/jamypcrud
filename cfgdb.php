<?php
require_once 'config.php';
class jamyp{
  private $con;
  function __construct($debug=false){
    $this->con= new PDO("mysql:host=".DBHOST.";dbname=".DBN,DBU,DBP,array(PDO::ATTR_PERSISTENT => true));
    $this->con->exec("set names utf8");
    $this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES,TRUE);
    if($debug){
    ini_set('display_errors', '1');
    $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }

  function eliminar_registro($tabla,$campoid,$idval){
    try{
      $sql="DELETE FROM ".$tabla." WHERE ".$campoid."=:idreg";
      $stmt=$this->con->prepare($sql);
      $borro=$stmt->execute(array(':idreg'=>$idval));
      if($borro!==false){
        echo "Registro eliminado correctamente";
      }else{
        echo "No se pudo eliminar el registro.Intente de nuevo.";
      }
    }catch(PDOException $e){
      throw $e;
    }
  }

  function guardar($tabla,$datos){
    //echo $datos;
    try{
      $dinsertar=json_decode($datos,true);
      $campos="";
      $valores="";
      $campos_arr=array();
      $campos_reln=array();
      foreach($dinsertar as $campo=>$valor){
      	if(strrpos($campo,"_reln_")===false){
        $campos.=$campo.",";
        $valores.=":".$campo.",";
        $campos_arr[":".$campo]=$valor;
      	}else{
      	$campos_reln[$campo]=$valor;
      	}
      }
      $campos=substr($campos,0,strlen($campos)-1);
      $valores=substr($valores,0,strlen($valores)-1);
      $sql="INSERT INTO ".$tabla." (".$campos.") VALUES(".$valores.")";
      //echo $sql;
      $stmt=$this->con->prepare($sql);
      $insertado=$stmt->execute($campos_arr);
      //print_r($campos_arr);
      if($insertado!=false){
      	//
      	$tablaid=$this->con->lastInsertId();
      	$tablaid++;
      	//print_r($campos_reln);
      	foreach($campos_reln as $c=>$v){
      		$nomcamp_split=explode("-",$c);
      		$campo_splited=explode("_",$nomcamp_split[0]);
      		$sql="INSERT INTO ".$tabla."_".$campo_splited[0]." (id".$campo_splited[0].",id".$tabla.") VALUES(:id".$campo_splited[0].",:id".$tabla.")";
      		$stmt_reln=$this->con->prepare($sql);
      		$insertado_reln=$stmt_reln->execute(array(':id'.$campo_splited[0]=>$v,":id".$tabla=>$tablaid));
      	}
      	//
        echo "Datos correctamente insertados";
      }else{
        echo "Falló inserción.Intente de nuevo";
      }
    }catch(PDOException $e){
      throw $e;
    }
  }

  function editar($tabla,$datos){
  	//echo $datos;
  	try{
  		$sql_colid="SELECT COLUMN_NAME as IDNOM FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = '".$tabla."' AND TABLE_SCHEMA='".DBN."' AND COLUMN_KEY='PRI'";
  		$stmt_colid=$this->con->prepare($sql_colid);
  		$stmt_colid->execute();
  		$colid=$stmt_colid->fetch();
  		$dinsertar=json_decode($datos,true);
  		$campos="";
  		$campos_arr=array();
  		$campos_reln=array();
  		foreach($dinsertar as $campo=>$valor){
  			if(strrpos($campo,"_reln_")===false){
  				if($campo!="llaveprim"){
  				$campos.=$campo."=:".$campo.",";
  				$campos_arr[":".$campo]=$valor;
  				}else{
  				$campos_arr[":llaveprim"]=$valor;
  				}
  			}else{
  				$campos_reln[$campo]=$valor;
  			}
  		}
  		$campos=substr($campos,0,strlen($campos)-1);
  		$sql="UPDATE ".$tabla." SET ".$campos." WHERE ".$colid["IDNOM"]."=:llaveprim";
  		//echo $sql;
      //print_r($campos_arr);
  		$stmt=$this->con->prepare($sql);
  		$actualizado=$stmt->execute($campos_arr);
  		if($actualizado!=false){
  			//
  			//print_r($campos_reln);
  			//limpiar tabla(s) relacional
  			foreach($campos_reln as $c=>$v){
  				$nomcamp_split=explode("-",$c);
  				$campo_splited=explode("_",$nomcamp_split[0]);
  				$sql_limpiar="DELETE FROM ".$tabla."_".$campo_splited[0]." WHERE ".$colid["IDNOM"]."=:idtbl";
  				$stmt_limpia=$this->con->prepare($sql_limpiar);
  				$limpiado=$stmt_limpia->execute(array(':idtbl'=>$campos_arr[":llaveprim"]));
  			}
  			foreach($campos_reln as $c=>$v){
  				$nomcamp_split=explode("-",$c);
  				$campo_splited=explode("_",$nomcamp_split[0]);
  				$sql="INSERT INTO ".$tabla."_".$campo_splited[0]." (id".$campo_splited[0].",id".$tabla.") VALUES(:id".$campo_splited[0].",:id".$tabla.")";
  				$stmt_reln=$this->con->prepare($sql);
  				$insertado_reln=$stmt_reln->execute(array(':id'.$campo_splited[0]=>$v,":".$colid["IDNOM"]=>$campos_arr[":llaveprim"]));
  			}
  			//
  			echo "Datos correctamente insertados";
  		}else{
  			echo "Falló inserción.Intente de nuevo";
  		}
  	}catch(PDOException $e){
  		throw $e;
  	}
  }


  function carga_formulario($tabla,$tipoform="nuevo",$id=""){
    try{
      $tabla=filter_var($tabla,FILTER_SANITIZE_STRING);
      $sql="SELECT COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY,IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_name = :tabla AND TABLE_SCHEMA='".DBN."'";
      $stmt = $this->con->prepare($sql);
      $stmt->execute(array(':tabla'=>$tabla));
      $res=$stmt->fetchAll();
      $idcol="";
      foreach($res as $c_nom=>$c_val){
      	if($c_val["COLUMN_KEY"]=="PRI"){
      		$idcol=$c_val["COLUMN_NAME"];
      		break;
      	}
      }
      if($tipoform=="editar"){
      	$sql_editar_tbl="SELECT * FROM ".$tabla." WHERE ".$idcol."=:id";
      	$stmt_editar_tbl=$this->con->prepare($sql_editar_tbl);
      	$stmt_editar_tbl->execute(array(':id'=>$id));
      	$editar_res=$stmt_editar_tbl->fetch();
      }

      $html="";
      $html.="<h4 class='panel panel-header'>".ucfirst($tipoform)." ".ucfirst($tabla)."<input type='button' title='Cerrar formulario' class='btn btn-danger btnCerrar' value='X' /></h4>";
      $formularios_cuenta=0;
      foreach($res as $columna_nom=>$columna_val){
      	$obligatorio=($columna_val["IS_NULLABLE"]=="NO")?"obligatorio":"";
        if($tipoform=="editar"){
		  if($columna_val["COLUMN_KEY"]=="PRI"){
          $html.="<input type='hidden' class='campo' campo-nombre='".$columna_val["COLUMN_NAME"]."' id='llaveprim' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' /> ";
		  }else{
		  $html.="<div class='form-group'>";
		  if(strrpos($columna_val["COLUMN_NAME"],"_upload")!==false || strrpos($columna_val["COLUMN_NAME"],"_relacional_")!==false || strrpos($columna_val["COLUMN_NAME"],"_radio_")!==false || strrpos($columna_val["COLUMN_NAME"],"_reln_")!==false){
		  	$campo_arr=explode("_",$columna_val["COLUMN_NAME"]);
		  	$html.="<div class='col-sm-4 text-right'><label>".ucfirst($campo_arr[0])."</label></div><div class='col-sm-8 text-left'>";
		  }else{
		  	$html.="<div class='col-sm-4 text-right'><label>".ucfirst(str_replace("_","&nbsp;",$columna_val["COLUMN_NAME"]))."</label></div><div class='col-sm-8 text-left'>";
		  }
		  if(strrpos($columna_val["COLUMN_NAME"],"_reln_")===false && strrpos($columna_val["COLUMN_NAME"],"_upload")===false && (strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false || strrpos($columna_val["COLUMN_TYPE"],"float")!==false || strrpos($columna_val["COLUMN_TYPE"],"double")!==false)){
		  	$html.="<input type='text' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if(strrpos($columna_val["COLUMN_NAME"],"_upload")!==false && strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false){
		  	$formularios_cuenta++;
		  	$html.="<form id='frm-upload-".$formularios_cuenta."' name='frm-upload-".$formularios_cuenta."' enctype='multipart/form-data'><input type='file' saved-url='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='upload campo ".$obligatorio."' frm='frm-upload-".$formularios_cuenta."' name='archivo' id='".$columna_val["COLUMN_NAME"]."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/></form>";
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"text")!==false){
		  	$html.="<textarea class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'>".$editar_res[$columna_val["COLUMN_NAME"]]."</textarea>";
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"tinyint")!==false){
		  	$checkado=($editar_res[$columna_val["COLUMN_NAME"]]=="1")?"checked":"";
		  	$html.="<input type='checkbox' class='campo ".$obligatorio."' ".$checkado." value='1' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && strrpos($columna_val["COLUMN_TYPE"],"tinyint")===false && strrpos($columna_val["COLUMN_NAME"],"_relacional_")===false && strrpos($columna_val["COLUMN_NAME"],"_radio_")===false){
		  	$html.="<input type='number' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if($columna_val["COLUMN_TYPE"]=="date"){
		  	$html.="<input type='date' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if($columna_val["COLUMN_TYPE"]=="datetime"){
		  	$html.="<input type='datetime-local' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if($columna_val["COLUMN_TYPE"]=="time"){
		  	$html.="<input type='time' value='".$editar_res[$columna_val["COLUMN_NAME"]]."' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && (strrpos($columna_val["COLUMN_NAME"],"_relacional_")!==false)){
		  	$rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
		  	$sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
		  	$stmt=$this->con->prepare($sql_getid);
		  	$stmt->execute(array(':tabla'=>$rel_arr[0]));
		  	$res_getid=$stmt->fetch();
		  	$sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
		  	$stmt_combo=$this->con->prepare($sql);
		  	$stmt_combo->execute();
		  	$opciones=$stmt_combo->fetchAll();
		  	$html.="<select class='campo form-control ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'>";
		  	$html.="<option value=''>Seleccionar...</option>";
		  	foreach($opciones as $op){
		  		$seleccionado=($editar_res[$columna_val["COLUMN_NAME"]]==$op["ID"])?"selected":"";
		  		$html.="<option ".$seleccionado." value='".$op["ID"]."'>".$op["Campo"]."</option>";
		  	}
		  	$html.="</select>";
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && strrpos($columna_val["COLUMN_NAME"],"_radio_")!==false){
		  	$rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
		  	$sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
		  	$stmt=$this->con->prepare($sql_getid);
		  	$stmt->execute(array(':tabla'=>$rel_arr[0]));
		  	$res_getid=$stmt->fetch();
		  	$sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
		  	$stmt_combo=$this->con->prepare($sql);
		  	$stmt_combo->execute();
		  	$opciones=$stmt_combo->fetchAll();
		  	$i=0;
		  	foreach($opciones as $op){
		  		$seleccionado=($editar_res[$columna_val["COLUMN_NAME"]]==$op["ID"])?"checked":"";
		  		$html.="<label for='".$op["Campo"]."'>".$op["Campo"]."</label><input type='radio' ".$seleccionado." class='campo' campo-nombre='".$columna_val["COLUMN_NAME"]."' name='".$columna_val["COLUMN_NAME"]."' value='".$op["ID"]."'/><br/>";
		  	}
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false && strrpos($columna_val["COLUMN_NAME"],"_reln_")!==false){
		  	$rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
		  	$sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
		  	$stmt=$this->con->prepare($sql_getid);
		  	$stmt->execute(array(':tabla'=>$rel_arr[0]));
		  	$res_getid=$stmt->fetch();
		  	$sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
		  	$stmt_combo=$this->con->prepare($sql);
		  	$stmt_combo->execute();
		  	$opciones=$stmt_combo->fetchAll();
		  	$i=0;
		  	$lista_seleccionados=explode(",",$editar_res[$columna_val["COLUMN_NAME"]]);
		  	foreach($opciones as $op){
		  		$i++;
		  		$seleccionado=(in_array($op["Campo"], $lista_seleccionados))?"checked":"";
		  		$html.="<label for='".$op["Campo"]."'>".$op["Campo"]."</label><input type='checkbox' ".$seleccionado." class='campo' campo-nombre='".$columna_val["COLUMN_NAME"]."-".$i."' name='".$columna_val["COLUMN_NAME"]."' value='".$op["ID"]."'/><br/>";
		  	}
		  }
		  if(strrpos($columna_val["COLUMN_TYPE"],"enum")!==false){
		  	$listado_str=str_replace("enum(","",$columna_val["COLUMN_TYPE"]);
		  	$listado_str=str_replace(")","",$listado_str);
		  	$listado_str=str_replace("'","",$listado_str);
		  	$listado_arr=explode(",",$listado_str);
		  	$html.="<select class='campo form-control ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'>";
		  	$html.="<option value=''>Seleccionar...</option>";
		  	foreach($listado_arr as $op){
		  		$seleccionado=($editar_res[$columna_val["COLUMN_NAME"]]==$op)?"checked":"";
		  		$html.="<option ".$seleccionado.">".$op."</option>";
		  	}
		  	$html.="</select>";
		  }
		  if($obligatorio!=""){
		  	$html.="<span class='asterisco-obligatorio'>*</span>";
		  }
		  $html.="</div></div>";
		  }
    }elseif($tipoform=="nuevo" && $columna_val["COLUMN_KEY"]!="PRI"){
          $html.="<div class='form-group'>";
          if(strrpos($columna_val["COLUMN_NAME"],"_upload")!==false || strrpos($columna_val["COLUMN_NAME"],"_relacional_")!==false || strrpos($columna_val["COLUMN_NAME"],"_radio_")!==false || strrpos($columna_val["COLUMN_NAME"],"_reln_")!==false){
          	$campo_arr=explode("_",$columna_val["COLUMN_NAME"]);
          	$html.="<div class='col-sm-4 text-right'><label>".ucfirst($campo_arr[0])."</label></div><div class='col-sm-8 text-left'>";
          }else{
          	$html.="<div class='col-sm-4 text-right'><label>".ucfirst(str_replace("_","&nbsp;",$columna_val["COLUMN_NAME"]))."</label></div><div class='col-sm-8 text-left'>";
          }
          if(strrpos($columna_val["COLUMN_NAME"],"_reln_")===false && strrpos($columna_val["COLUMN_NAME"],"_upload")===false && (strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false || strrpos($columna_val["COLUMN_TYPE"],"float")!==false || strrpos($columna_val["COLUMN_TYPE"],"double")!==false)){
            $html.="<input type='text' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if(strrpos($columna_val["COLUMN_NAME"],"_upload")!==false && strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false){
          	$formularios_cuenta++;
            $html.="<form id='frm-upload-".$formularios_cuenta."' name='frm-upload-".$formularios_cuenta."' enctype='multipart/form-data'><input type='file' saved-url='' class='upload campo ".$obligatorio."' frm='frm-upload-".$formularios_cuenta."' name='archivo' id='".$columna_val["COLUMN_NAME"]."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/></form>";
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"text")!==false){
          	$html.="<textarea class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'></textarea>";
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"tinyint")!==false){
            $html.="<input type='checkbox' class='campo ".$obligatorio."' value='1' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && strrpos($columna_val["COLUMN_TYPE"],"tinyint")===false && strrpos($columna_val["COLUMN_NAME"],"_relacional_")===false && strrpos($columna_val["COLUMN_NAME"],"_radio_")===false){
            $html.="<input type='number' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if($columna_val["COLUMN_TYPE"]=="date"){
          	$html.="<input type='date' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if($columna_val["COLUMN_TYPE"]=="datetime"){
          	$html.="<input type='datetime-local' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if($columna_val["COLUMN_TYPE"]=="time"){
          	$html.="<input type='time' class='form-control campo ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'/>";
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && strrpos($columna_val["COLUMN_NAME"],"_relacional_")!==false){
            $rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
            $sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
            $stmt=$this->con->prepare($sql_getid);
            $stmt->execute(array(':tabla'=>$rel_arr[0]));
            $res_getid=$stmt->fetch();
            $sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
            $stmt_combo=$this->con->prepare($sql);
            $stmt_combo->execute();
            $opciones=$stmt_combo->fetchAll();
            $html.="<select class='campo form-control ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'>";
            $html.="<option value=''>Seleccionar...</option>";
            foreach($opciones as $op){
              $html.="<option value='".$op["ID"]."'>".$op["Campo"]."</option>";
            }
            $html.="</select>";
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"int")!==false && strrpos($columna_val["COLUMN_NAME"],"_radio_")!==false){
            $rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
            $sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
            $stmt=$this->con->prepare($sql_getid);
            $stmt->execute(array(':tabla'=>$rel_arr[0]));
            $res_getid=$stmt->fetch();
            $sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
            $stmt_combo=$this->con->prepare($sql);
            $stmt_combo->execute();
            $opciones=$stmt_combo->fetchAll();
            $i=0;
            foreach($opciones as $op){
              $html.="<label for='".$op["Campo"]."'>".$op["Campo"]."</label><input type='radio' class='campo' campo-nombre='".$columna_val["COLUMN_NAME"]."' name='".$columna_val["COLUMN_NAME"]."' value='".$op["ID"]."'/><br/>";
            }
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"varchar")!==false && strrpos($columna_val["COLUMN_NAME"],"_reln_")!==false){
          	$rel_arr=explode("_",$columna_val["COLUMN_NAME"]);
          	$sql_getid="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = :tabla AND COLUMN_KEY='PRI' AND TABLE_SCHEMA= '".DBN."';";
          	$stmt=$this->con->prepare($sql_getid);
          	$stmt->execute(array(':tabla'=>$rel_arr[0]));
          	$res_getid=$stmt->fetch();
          	$sql="SELECT ".$res_getid["COLUMN_NAME"]." as ID, ".$rel_arr[2]." as Campo FROM ".$rel_arr[0];
          	$stmt_combo=$this->con->prepare($sql);
          	$stmt_combo->execute();
          	$opciones=$stmt_combo->fetchAll();
          	$i=0;
          	foreach($opciones as $op){
          		$i++;
          		$html.="<label for='".$op["Campo"]."'>".$op["Campo"]."</label><input type='checkbox' class='campo' campo-nombre='".$columna_val["COLUMN_NAME"]."-".$i."' name='".$columna_val["COLUMN_NAME"]."' value='".$op["ID"]."'/><br/>";
          	}
          }
          if(strrpos($columna_val["COLUMN_TYPE"],"enum")!==false){
            $listado_str=str_replace("enum(","",$columna_val["COLUMN_TYPE"]);
            $listado_str=str_replace(")","",$listado_str);
            $listado_str=str_replace("'","",$listado_str);
            $listado_arr=explode(",",$listado_str);
            $html.="<select class='campo form-control ".$obligatorio."' campo-nombre='".$columna_val["COLUMN_NAME"]."'>";
            $html.="<option value=''>Seleccionar...</option>";
            foreach($listado_arr as $op){
              $html.="<option>".$op."</option>";
            }
            $html.="</select>";
          }
          if($obligatorio!=""){
            $html.="<span class='asterisco-obligatorio'>*</span>";
          }
          $html.="</div></div>";
        }
        $html.="<div class='clearfix'>&nbsp;</div>";
      }
      $html.="<div class='clearfix'>&nbsp;</div>";
      if($tipoform=="editar"){
      	$html.="<div class='form-group text-center'><input type='button' value='Editar' id='btnEditar' class='btn btn-primary' /></div>";
      }else{
      $html.="<div class='form-group text-center'><input type='button' value='Guardar' id='btnGuardar' class='btn btn-primary' /></div>";
      }
      echo $html;
    }catch(PDOException $e){
    throw $e;
    }
  }

  function eliminar($tabla,$condicion_1="",$condicion_2="",$esfecha="false"){
    try{
      $sql="DELETE FROM ".$tabla." WHERE ";
      $condicion_params=array();
      $condicion_str="";
      if($esfecha=="true"){
        if($condicion_1!='' && $condicion_2!=''){
          list($k,$v)=explode("|",$condicion_1);
            $condicion_params[':'.$k."_1"]=$v;
            $condicion_str.=$k.">=:".$k."_1";
          list($k2,$v2)=explode("|",$condicion_2);
            $condicion_str.=" AND ";
            $condicion_str.=$k2."<=:".$k2."_2";
            $condicion_params[':'.$k2."_2"]=$v2;
          }elseif($condicion_1!='' && $condicion_2==''){
            list($k,$v)=explode("|",$condicion_1);
              $condicion_params[':'.$k."_p"]=$v;
              $condicion_str.=$k.">=:".$k."_p";
          }elseif($condicion_1=='' && $condicion_2!=''){
            list($k,$v)=explode("|",$condicion_2);
              $condicion_params[':'.$k."_p"]=$v;
              $condicion_str.=$k."<=:".$k."_p";
          }
      }else{
        if($condicion_1!=''){
        list($k,$v)=explode("|",$condicion_1);
          $condicion_params[':'.$k."1"]=$v;
          $condicion_str.=$k."=:".$k."1";
        }
        if($condicion_2!=''){
        list($k2,$v2)=explode("|",$condicion_2);
          $condicion_str.=" AND ";
          $condicion_str.=$k2."=:".$k2."2";
          $condicion_params[':'.$k2."2"]=$v2;
        }
      }
      $sql.=$condicion_str;
      //echo $sql;
      //print_r($condicion_params);
      $stmt = $this->con->prepare($sql);
      $borrado=$stmt->execute($condicion_params);
      if($borrado!==false){
          echo "1";
      }else{
          echo "0";
      }
    }catch(PDOException $e){
      throw $e;
    }
  }

  function total($nombre_tabla,$condicion_1="",$condicion_2="",$esfecha=false){
    try{
      global $con;
      $tabla=filter_var($nombre_tabla,FILTER_SANITIZE_STRING);
      $sql="SELECT count(1) as cuantos FROM ".$tabla;
      $condicion_params=array();
      $condicion_str="";
      if($esfecha=="true"){
        if($condicion_1!="" && $condicion_2!=""){
          //echo "------------entre aca0-----------";
          $condicion_str=" WHERE ";
          list($k,$v)=explode("|",$condicion_1);
            $condicion_params[":".$k."_1"]=$v;
            $condicion_str.=$k.">=:".$k."_1";
          list($k2,$v2)=explode("|",$condicion_2);
            $condicion_str.=" AND ";
            $condicion_str.=$k2."<=:".$k2."_2";
            $condicion_params[":".$k2."_2"]=$v2;
          }elseif($condicion_1!="" && $condicion_2==""){
            //echo "------------entre aca1-----------";
            $condicion_str=" WHERE ";
            list($k,$v)=explode("|",$condicion_1);
              $condicion_params[":".$k."_p"]=$v;
              $condicion_str.=$k.">=:".$k."_p";
          }elseif($condicion_1=="" && $condicion_2!=""){
            //echo "------------entre aca2-----------";
            $condicion_str=" WHERE ";
            list($k,$v)=explode("|",$condicion_2);
              $condicion_params[":".$k."_p"]=$v;
              $condicion_str.=$k."<=:".$k."_p";
          }
      }else{
        if($condicion_1!="" || $condicion_2!=""){
          $condicion_str=" WHERE ";
        }
        if($condicion_1!=""){
          //echo "------------entre aca3-----------";
        list($k,$v)=explode("|",$condicion_1);
          $condicion_params[":".$k."1"]=$v;
          $condicion_str.=$k."=:".$k."1";
        }
        if($condicion_2!=""){
          //echo "------------entre aca4-----------";
        list($k2,$v2)=explode("|",$condicion_2);
          $condicion_str.=" AND ";
          $condicion_str.=$k2."=:".$k2."2";
          $condicion_params[":".$k2."2"]=$v2;
        }
      }
      if($condicion_1!="" || $condicion_2!=""){
        $sql.=$condicion_str;
      }
      $stmt = $this->con->prepare($sql);
      //echo $sql;
      //print_r($condicion_params);
      if($condicion_1!="" || $condicion_2!=""){
      $stmt->execute($condicion_params);
      }else{
      $stmt->execute();
      }
      $cuantos=$stmt->fetch();
      echo $cuantos['cuantos'];
    }catch(PDOException $e){
      throw $e;
    }
  }

  function crear_tabla($nombre_tabla,$excluir="",$ordenarpor="",$direc="asc",$punto="0",$condicion_1="",$condicion_2="",$esfecha=false){
    try{
    $sql="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".DBN."' AND `TABLE_NAME`=:tbl";
    if($excluir!=""){
        $sql.=" AND COLUMN_NAME NOT IN(:col)";
    }
    $stmt = $this->con->prepare($sql);
    if($excluir!=""){
    $stmt->execute(array(':tbl'=>$nombre_tabla,':col'=>$excluir));
    }else{
    $stmt->execute(array(':tbl'=>$nombre_tabla));
    }
    $res=$stmt->fetchAll();
    $html="";
    $html.="<tr><th>Acciones</th>";
    $columnas="";
    $csv_str="";
    foreach ($res as $key => $value) {
      foreach($value as $k=>$v){
        if(!is_numeric($k)){
          $columnas.=$v.",";
          $csv_str.=$v.",";
          	if(strripos($v, "_radio_")!==false || strripos($v, "_upload")!==false || strripos($v, "_relacional_")!==false || strripos($v, "_reln_")!==false){
				$campo_nombre=explode("_",$v);
				$html.="<th class='ordenapor text-primary alert alert-info' id='".$v."' title='Ordenar por ".$campo_nombre[0]."'>".ucfirst($campo_nombre[0])."</th>";
          	}else{
        	$html.="<th class='ordenapor text-primary alert alert-info' id='".$v."' title='Ordenar por ".$v."'>".ucfirst(str_replace("_","&nbsp;",$v))."</th>";
          	}
        }
      }
    }
    $csv_str=substr($csv_str,0,strlen($csv_str)-1);
    $csv_str.="\n";
    $html.="</tr>";
    $tabla=filter_var($nombre_tabla,FILTER_SANITIZE_STRING);
    $sql="SELECT count(1) as cuantos FROM ".$tabla;
    $condicion_params=array();
    $condicion_str="";
    if($esfecha=="true"){
      if($condicion_1!="" && $condicion_2!=""){
        //echo "------------entre aca0-----------";
        $condicion_str=" WHERE ";
        list($k,$v)=explode("|",$condicion_1);
          $condicion_params[":".$k."_1"]=$v;
          $condicion_str.=$k.">=:".$k."_1";
        list($k2,$v2)=explode("|",$condicion_2);
          $condicion_str.=" AND ";
          $condicion_str.=$k2."<=:".$k2."_2";
          $condicion_params[":".$k2."_2"]=$v2;
        }elseif($condicion_1!="" && $condicion_2==""){
          //echo "------------entre aca1-----------";
          $condicion_str=" WHERE ";
          list($k,$v)=explode("|",$condicion_1);
            $condicion_params[":".$k."_p"]=$v;
            $condicion_str.=$k.">=:".$k."_p";
        }elseif($condicion_1=="" && $condicion_2!=""){
          //echo "------------entre aca2-----------";
          $condicion_str=" WHERE ";
          list($k,$v)=explode("|",$condicion_2);
            $condicion_params[":".$k."_p"]=$v;
            $condicion_str.=$k."<=:".$k."_p";
        }
    }else{
      if($condicion_1!="" || $condicion_2!=""){
        $condicion_str=" WHERE ";
      }
      if($condicion_1!=""){
        //echo "------------entre aca3-----------";
      list($k,$v)=explode("|",$condicion_1);
        if(strrpos($k,"_relacional_")!==false || strrpos($k,"_radio_")!==false){
          $desglose=explode("_",$k);
          $condicion_str.=$k."=(SELECT id".$desglose[0]." FROM ".$desglose[0]." WHERE ".$desglose[2]." LIKE :".$k."1)";
          $condicion_params[":".$k."1"]="%".$v."%";
        }elseif(strrpos($k,"_reln_")!==false){
          $desglose=explode("_",$k);
          $condicion_str.="id".$tabla." IN(SELECT id".$tabla." FROM ".$tabla."_".$desglose[0]." as T1,".$desglose[0]." as T2 WHERE T1.id".$desglose[0]."=T2.id".$desglose[0]." AND T2.".$desglose[2]." like :".$k."1)";
          $condicion_params[":".$k."1"]="%".$v."%";
        }else{
        $condicion_params[":".$k."1"]=$v;
        $condicion_str.=$k."=:".$k."1";
        }
      }
      if($condicion_2!=""){
        //echo "------------entre aca4-----------";
      list($k2,$v2)=explode("|",$condicion_2);
        $condicion_str.=" AND ";
        if(strrpos($k2,"_relacional_")!==false || strrpos($k2,"_radio_")!==false ){
          $desglose2=explode("_",$k2);
          $condicion_str.=$k2."=(SELECT id".$desglose2[0]." FROM ".$desglose2[0]." WHERE ".$desglose2[2]." LIKE :".$k2."2)";
          $condicion_params[":".$k2."2"]="%".$v2."%";
        }else{
        $condicion_str.=$k2."=:".$k2."2";
        $condicion_params[":".$k2."2"]=$v2;
        }
      }
    }
    if($condicion_1!="" || $condicion_2!=""){
      $sql.=$condicion_str;
    }
    $stmt = $this->con->prepare($sql);
    //echo $sql;
    //print_r($condicion_params);
    if($condicion_1!="" || $condicion_2!=""){
    $stmt->execute($condicion_params);
    }else{
    $stmt->execute();
    }
    //echo $sql;
    $cuantos=$stmt->fetch();
    if($cuantos['cuantos']==0){
      $html.="<script>alert('No hay registros o no coinciden datos con la búsqueda');</script>";
    }
    if($punto>$cuantos['cuantos']){
      die("0");
    }
    $columnas=substr($columnas,0,strlen($columnas)-1);
    $numcolumnas=count(explode(",",$columnas));
    //
    $sql_id="SELECT COLUMN_NAME as ID
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '".DBN."'
   AND TABLE_NAME = :tabla
   AND COLUMN_KEY = 'PRI'";
    $stmt_id=$this->con->prepare($sql_id);
    $stmt_id->execute(array(':tabla'=>$nombre_tabla));
    $res_id=$stmt_id->fetch();
    //
    $sql="SELECT ".$columnas." FROM ".$tabla;
    $sql_idval="SELECT ".$res_id['ID']." as IDNOMBRE FROM ".$tabla;
    $csv_sql="SELECT ".$columnas." FROM ".$tabla;
      if($condicion_1!="" || $condicion_2!=""){
    $condicion_params=array();
    $condicion_str=" WHERE ";
    if($esfecha=="true"){
      if($condicion_1!='' && $condicion_2!=''){
        list($k,$v)=explode("|",$condicion_1);
          $condicion_params[':'.$k."_1"]=$v;
          $condicion_str.=$k.">=:".$k."_1";
        list($k2,$v2)=explode("|",$condicion_2);
          $condicion_str.=" AND ";
          $condicion_str.=$k2."<=:".$k2."_2";
          $condicion_params[':'.$k2."_2"]=$v2;
        }elseif($condicion_1!='' && $condicion_2==''){
          list($k,$v)=explode("|",$condicion_1);
            $condicion_params[':'.$k."_p"]=$v;
            $condicion_str.=$k.">=:".$k."_p";
        }elseif($condicion_1=='' && $condicion_2!=''){
          list($k,$v)=explode("|",$condicion_2);
            $condicion_params[':'.$k."_p"]=$v;
            $condicion_str.=$k."<=:".$k."_p";
        }
    }else{
      if($condicion_1!=''){
      list($k,$v)=explode("|",$condicion_1);
        if(strrpos($k,"_relacional_")!==false || strrpos($k,"_radio_")!==false ){
          $desglose=explode("_",$k);
          $condicion_str.=$k."=(SELECT id".$desglose[0]." FROM ".$desglose[0]." WHERE ".$desglose[2]." LIKE :".$k."1)";
          $condicion_params[":".$k."1"]="%".$v."%";
        }elseif(strrpos($k,"_reln_")!==false){
          $desglose=explode("_",$k);
          $condicion_str.="id".$tabla." IN(SELECT id".$tabla." FROM ".$tabla."_".$desglose[0]." as T1,".$desglose[0]." as T2 WHERE T1.id".$desglose[0]."=T2.id".$desglose[0]." AND T2.".$desglose[2]." like :".$k."1)";
          $condicion_params[":".$k."1"]="%".$v."%";
        }else{
        $condicion_params[":".$k."1"]=$v;
        $condicion_str.=$k."=:".$k."1";
        }
      }
      if($condicion_2!=''){
      list($k2,$v2)=explode("|",$condicion_2);
        $condicion_str.=" AND ";
        if(strrpos($k2,"_relacional_")!==false || strrpos($k2,"_radio_")!==false ){
          $desglose2=explode("_",$k2);
          $condicion_str.=$k2."=(SELECT id".$desglose2[0]." FROM ".$desglose2[0]." WHERE ".$desglose2[2]." LIKE :".$k2."2)";
          $condicion_params[":".$k2."2"]="%".$v2."%";
        }else{
        $condicion_str.=$k2."=:".$k2."2";
        $condicion_params[':'.$k2."2"]=$v2;
        }
      }
    }
  $sql.=$condicion_str;
  $sql_idval.=$condicion_str;
  $csv_sql.=$condicion_str;
    }
    if($ordenarpor!=""){
        $sql.=" ORDER BY ".$ordenarpor." ".$direc;
        $sql_idval.=" ORDER BY ".$ordenarpor." ".$direc;
    }else{
      $sql_idval.=" ORDER BY ".$res_id['ID'];
    }
    if($punto=="-1"){
      if($cuantos['cuantos']>15){
      $punto=$cuantos['cuantos']-15;
      }else{
      $punto=0;
      }
    }
    if($punto!="-2"){
    $sql.=" LIMIT $punto,15";
    $sql_idval.=" LIMIT $punto,15";
    }
    //echo $sql;
    //echo "-------idval------";
    //echo $sql_idval;
    $stmt = $this->con->prepare($sql);
    $stmt2= $this->con->prepare($csv_sql);
    $stmtids=$this->con->prepare($sql_idval);
    if($condicion_1!="" || $condicion_2!=""){
    $stmt->execute($condicion_params);
    $stmt2->execute($condicion_params);
    $stmtids->execute($condicion_params);
    }else{
    $stmt->execute();
    $stmt2->execute();
    $stmtids->execute();
    }
    $res=$stmt->fetchAll();
    $res2=$stmt2->fetchAll();
    $res_idregs=$stmtids->fetchAll();
    $nextid=0;
    foreach($res as $valor){
      $html.="<tr><td><img src='editar.png' title='Editar registro' id-campo='".$res_id['ID']."' id-registro='".$res_idregs[$nextid]['IDNOMBRE']."' class='btnaccion editar-registro'/>&nbsp;<img src='delete.png' title='Eliminar registro' id-campo='".$res_id['ID']."' id-registro='".$res_idregs[$nextid]['IDNOMBRE']."' class='btnaccion elimina-registro'/></td>";
      foreach ($valor as $campo=>$valcamp) {
        if(!is_numeric($campo)){
        	if(strrpos($campo,"_relacional_")!==false || strrpos($campo,"_radio_")!==false || strrpos($campo,"_reln_")!==false){
        		$c_split=explode("_",$campo);
        		$sql_idtbl="SELECT COLUMN_NAME as ID
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '".DBN."'
   AND TABLE_NAME = :tabla
   AND COLUMN_KEY = 'PRI'";
        		$stmt_idtbl=$this->con->prepare($sql_idtbl);
        		$stmt_idtbl->execute(array(':tabla'=>$c_split[0]));
        		$res_idtbl=$stmt_idtbl->fetch();
        		switch ($c_split[1]){
        			case "relacional":
        			case "radio":
        				$sql="SELECT ".$c_split[2]." as campo FROM ".$c_split[0]." WHERE ".$res_idtbl["ID"]."=:id";
        				$stmt_col=$this->con->prepare($sql);
        				$stmt_col->execute(array(':id'=>$valcamp));
        				$campo_res=$stmt_col->fetch();
        				$html.="<td title='".$campo."'>".$campo_res["campo"]."</td>";
        			break;
        			case "reln":
        				$sql="SELECT T1.".$c_split[2]." as campo FROM ".$c_split[0]." T1,".$nombre_tabla." T2,".$nombre_tabla."_".$c_split[0]." T3
        					WHERE T1.".$res_idtbl["ID"]."=T3.".$res_idtbl["ID"]."
        					AND T2.".$res_id['ID']."=T3.".$res_id['ID']."
        					AND T3.".$res_id["ID"]."=:id";
        				$stmt_col=$this->con->prepare($sql);
        				$stmt_col->execute(array(':id'=>$res_idregs[$nextid]['IDNOMBRE']));
        				$campo_res=$stmt_col->fetchAll();
        				$listado_str="";
        				//echo $sql;
        				//print_r($campo_res);
        				foreach($campo_res as $k=>$v){
        					$listado_str.=$v["campo"].",";
        				}
        				$listado_str=substr($listado_str, 0,strlen($listado_str)-1);
        				$html.="<td title='".$campo."'>".$listado_str."</td>";
        			break;
        		}
        	}else{
        	$html.="<td title='".$campo."'>$valcamp</td>";
        	}
        }
      }
      $nextid++;
      $html.="</tr>";
    }
    foreach($res2 as $valor){
      foreach ($valor as $campo=>$valcamp) {
        if(!is_numeric($campo)){
        $csv_str.=$valcamp.",";
        }
      }
      $csv_str=substr($csv_str,0,strlen($csv_str)-1);
      $csv_str.="\n";
    }
    echo $html;
    $this->crear_csv($csv_str);
  }catch(PDOException $e){
    throw $e;
    }
  }

  function completa_combo($nombre_tabla,$excluir=""){
    try{
    $sql="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".DBN."' AND `TABLE_NAME`=:tbl";
    if($excluir!=""){
        $sql.=" AND COLUMN_NAME NOT IN(:col)";
    }
    $stmt = $this->con->prepare($sql);
    if($excluir!=""){
    $stmt->execute(array(':tbl'=>$nombre_tabla,':col'=>$excluir));
    }else{
    $stmt->execute(array(':tbl'=>$nombre_tabla));
    }
    $res=$stmt->fetchAll();
    echo "<option value=''>Seleccionar...</option>";
    foreach ($res as $arr) {
      foreach($arr as $c=>$val){
        if(!is_numeric($c)){
          if(strrpos($val,"_relacional_")!==false || strrpos($val,"_radio_")!==false || strrpos($val,"_reln_")!==false || strrpos($val,"_upload")!==false){
            $c_n=explode("_",$val);
              echo "<option value='".$val."'>".ucfirst($c_n[0])."</option>";
          }else{
          echo "<option value='".$val."'>".ucfirst(str_replace("_","&nbsp;",$val))."</option>";
          }
        }
      }
    }
    }catch(PDOException $e){
    throw $e;
    }
  }

  function crear_csv($contenido){
    $archivo = fopen("csv/archivo.csv", "w+");
    fwrite($archivo, $contenido);
    fclose($archivo);
  }

  function combo_tablas(){
    try{
      $sql="SHOW TABLES";
      $stmt = $this->con->prepare($sql);
      $stmt->execute();
      $res=$stmt->fetchAll();
      $html="<option value=''>Seleccionar...</option>";
      foreach($res as $tbl=>$tblnom){
          foreach($tblnom as $tk=>$t){
            if(!is_numeric($tk)){
          $html.="<option value='".$t."'>".str_replace("_","&nbsp;",$t)."</option>";
            }
          }
      }
      echo $html;
    }catch(PDOException $e){
      throw $e;
    }
  }

  function subir_archivo(){
  	date_default_timezone_set("America/Monterrey");
  	$fecha=date("Y-m-d H:i:s");
  	$fecha_formateada=str_replace("-", "", $fecha);
  	$fecha_formateada=str_replace(":", "", $fecha_formateada);
  	$fecha_formateada=str_replace(" ", "", $fecha_formateada);
  	if(move_uploaded_file($_FILES['archivo']['tmp_name'], "uploads/".$fecha_formateada."-".$_FILES["archivo"]["name"])){
  		echo "uploads/".$fecha_formateada."-".$_FILES["archivo"]["name"];
  	}else{
  		echo "0";
  	}
  }

  function CerrarConexion(){
    $this->con=null;
  }

}
$docrud= new jamyp(true);
if(isset($_FILES["archivo"]["name"])){
	$docrud->subir_archivo();
}else{
//$nombre_tabla,$excluir="",$ordenarpor="",$direc="asc",$punto="0",$buscar=""
switch($_POST['accion']){
  case 'rellenartbl':
    $docrud->crear_tabla($_POST['tbl'],$_POST['exc']);
  break;
  case 'ordenatbl':
    $docrud->crear_tabla($_POST['tbl'],$_POST['exc'],$_POST['criterio'],$_POST['direccion'],"0",$_POST['busc1'],$_POST['busc2'],$_POST['es_fecha']);
  break;
  case 'desplaza':
    $docrud->crear_tabla($_POST['tbl'],$_POST['exc'],$_POST['criterio'],$_POST['direccion'],$_POST['offset'],$_POST['busc1'],$_POST['busc2'],$_POST['es_fecha']);
  break;
  case 'rellenacombo':
    $docrud->completa_combo($_POST['tbl'],$_POST['exc']);
  break;
  case 'filtrartbl':
    $docrud->crear_tabla($_POST['tbl'],$_POST['exc'],"","asc",$_POST['offset'],$_POST['busc1'],$_POST['busc2'],$_POST['es_fecha']);
  break;
  case 'eliminar':
    $docrud->eliminar($_POST['tbl'],$_POST['condicion1'],$_POST['condicion2'],$_POST['es_fecha']);
  break;
  case 'calculatotal':
    $docrud->total($_POST['tbl'],$_POST['busc1'],$_POST['busc2'],$_POST['es_fecha']);
  break;
  case 'combotablas':
    $docrud->combo_tablas();
  break;
  case 'eliminar-registro':
    $docrud->eliminar_registro($_POST['tbl'],$_POST['campo'],$_POST['id']);
  break;
  case 'nuevo-registro':
    $docrud->carga_formulario($_POST['tbl']);
  break;
  case 'guardar':
    $docrud->guardar($_POST['tbl'],$_POST['valores']);
  break;
  case 'editar-registro':
    $docrud->carga_formulario($_POST['tbl'],"editar",$_POST['id']);
  break;
  case 'editar':
  	$docrud->editar($_POST['tbl'],$_POST['valores']);
  break;
}

}
$docrud->CerrarConexion();
?>
