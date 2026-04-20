<form method="POST" enctype="multipart/form-data">

<div class="mb-3">
<label>Archivo PFX</label>

<input type="file"
name="certificado"
class="form-control">
</div>

<button class="btn btn-primary">
Subir certificado
</button>

</form>
<form method="POST"
action="index.php?module=certificados&action=guardar">

<input type="text" name="dni" class="form-control" placeholder="DNI">

<input type="date" name="fecha_emision">

<input type="date" name="fecha_vencimiento">

<select name="tipo_certificado">
<option>FIRMA DIGITAL</option>
<option>AUTENTICACION</option>
</select>

<button class="btn btn-success">
Guardar
</button>

</form>