<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();
?>
<!-- Content -->
<div class="content">
  <div class="animated fadeIn">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10">
        <div class="card mt-5 shadow">
          <div class="card-header text-center bg-gradient-primary text-white" style="background: linear-gradient(90deg,#1e3c72,#2a5298);">
            <h2 class="card-title mb-0"><i class="fa fa-users"></i> Usuarios Registrados</h2>
          </div>
          <div class="card-body">

            <p class="text-center mb-3">
              <i class="fa fa-info-circle text-info"></i>
              Lista de usuarios registrados en el sistema.
            </p>

          <div class="mb-3 text-right">
            <a href="crearUsuario.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nuevo Usuario</a>
          </div>

            <div class="table-responsive">
              <table id="datatables" class="table table-bordered table-hover table-striped align-middle">
                <thead class="thead-dark">
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Rol</th>
                    <th class="text-center">Estado Perfil</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    // Consulta con verificación de perfil completo
                    $stmt = $conn->prepare("
                        SELECT 
                            u.id, u.nombre, u.cedula, u.correo, u.rol,
                            CASE 
                                WHEN u.rol = 'admin' THEN 'N/A'
                                WHEN u.rol = 'estudiante' THEN 
                                    CASE WHEN e.id IS NOT NULL AND e.aldea_id IS NOT NULL AND e.parroquia IS NOT NULL 
                                         AND e.institucion_procedencia IS NOT NULL AND e.nacionalidad IS NOT NULL 
                                         AND e.genero IS NOT NULL AND e.religion IS NOT NULL 
                                         THEN 'Completo' ELSE 'Pendiente' END
                                WHEN u.rol = 'profesor' THEN 
                                    CASE WHEN p.id IS NOT NULL AND p.aldea_id IS NOT NULL AND p.especialidad IS NOT NULL 
                                         AND p.titulo IS NOT NULL 
                                         THEN 'Completo' ELSE 'Pendiente' END
                                WHEN u.rol = 'coordinador' THEN 
                                    CASE WHEN c.id IS NOT NULL AND c.aldea_id IS NOT NULL AND c.fecha_inicio_gestion IS NOT NULL 
                                         THEN 'Completo' ELSE 'Pendiente' END
                                ELSE 'Pendiente'
                            END AS estado_perfil
                        FROM usuarios u
                        LEFT JOIN estudiantes e ON u.id = e.usuario_id
                        LEFT JOIN profesores p ON u.id = p.usuario_id
                        LEFT JOIN coordinadores c ON u.id = c.usuario_id
                        ORDER BY u.nombre
                    ");
                    $stmt->execute();
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($usuarios && count($usuarios) > 0) {
                      foreach ($usuarios as $row) {
                        $id = $row['id'];
                        $nombre = htmlspecialchars($row['nombre']);
                        $cedula = htmlspecialchars($row['cedula']);
                        $correo = htmlspecialchars($row['correo']);
                        $rol = htmlspecialchars($row['rol']);
                        $estado_perfil = $row['estado_perfil'];
                        
                        // Definir clase y icono según estado
                        $badge_class = '';
                        $icono = '';
                        if ($estado_perfil === 'Completo') {
                            $badge_class = 'badge-success';
                            $icono = '<i class="fa fa-check-circle"></i>';
                        } elseif ($estado_perfil === 'Pendiente') {
                            $badge_class = 'badge-warning';
                            $icono = '<i class="fa fa-exclamation-triangle"></i>';
                        } else {
                            $badge_class = 'badge-secondary';
                            $icono = '<i class="fa fa-minus-circle"></i>';
                        }

                        echo "<tr>";
                        echo "<td>$id</td>";
                        echo "<td>$nombre</td>";
                        echo "<td>$cedula</td>";
                        echo "<td><span class='badge badge-info'>" . ucfirst($rol) . "</span></td>";
                        echo "<td class='text-center'><span class='badge $badge_class'>$icono $estado_perfil</span></td>";
                        echo "<td class='text-center'>

                          <button type='button' 
                            class='btn btn-sm btn-outline-info mx-1' 
                            title='Ver' 
                            data-toggle='modal' 
                            data-target='#modalUsuario$id'>
                            <i class='fa fa-eye'></i>
                          </button>

                           <form action='editarUsuario.php' method='POST' style='display: inline-block; margin: 0; padding: 0;'>
                              <input type='hidden' name='id' value='" . $id . "'>
                                <button type='submit' class='btn btn-sm btn-outline-primary mx-1' title='Editar'>
                                  <i class='fa fa-edit'></i>
                                </button>
                            </form>

                          <button type='button' 
                            class='btn btn-sm btn-outline-danger mx-1' 
                            title='Eliminar' 
                            data-toggle='modal' 
                            data-target='#modalUsuarioEliminar$id'>
                            <i class='fa fa-trash'></i>
                          </button>

                        
                          </td>";
                        echo "</tr>";



                        // Modal por usuario
                        echo "
                        <div class='modal fade'
                          id='modalUsuario$id' 
                          tabindex='-1'
                          role='dialog' 
                          aria-labelledby='modalUsuarioLabel$id' 
                          aria-hidden='true'>

                          <div class='modal-dialog modal-dialog-centered' role='document'>
                          <div class='modal-content'>
                          <div class='modal-header bg-info text-white'>
                            <h6  class='modal-title' id='modalUsuarioLabel$id'>Detalles del Usuario</h6>

                          <button type='button' class='close text-white' data-dismiss='modal' aria-label='Cerrar'>
                                  <span aria-hidden='true'>&times;</span>
                          </button>

                              </div>
                              <div class='modal-body'>
                                 <ul class='list-group'>
                                    <li class='list-group-item list-group-item-primary '>$nombre</li>
                                    <li class='list-group-item'>$cedula</li>
                                    <li class='list-group-item'> $correo</li>
                                    <li class='list-group-item'>$rol</li>
                                  </ul>                              
                                </div>
                              <div class='modal-footer'>
                                <button type='button' class='btn btn-secondary' data-dismiss='modal'>Cerrar</button>
                              </div>
                            </div>
                          </div>
                        </div>";
                       

                        // Modal de confirmación de eliminación
echo "
<div class='modal fade' 
  id='modalUsuarioEliminar$id' 
  tabindex='-1' 
  role='dialog' 
  aria-labelledby='modalUsuarioEliminarLabel$id' 
  aria-hidden='true'>

  <div class='modal-dialog modal-dialog-centered' role='document'>
    <div class='modal-content'>
      <div class='modal-header bg-danger text-white'>
        <h6 class='modal-title' id='modalUsuarioEliminarLabel$id'>Confirmar Eliminación</h6>
        <button type='button' class='close text-white' data-dismiss='modal' aria-label='Cerrar'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>

      <div class='modal-body'>
        <form action='../../controladores/usuarioController/eliminarUsuario.php' method='POST'>
          <input type='hidden' name='id' value='" . $id . "'>
          <p>¿Estás seguro de que deseas eliminar al usuario <strong>" . htmlspecialchars($nombre) . "</strong>?</p>
          <p class='text-danger'><small>Esta acción no se puede deshacer.</small></p>

          <div class='modal-footer'>
            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancelar</button>
            <button type='submit' class='btn btn-danger'>Eliminar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>";
                      }
                    } else {
                      echo "<tr><td colspan='6' class='text-center'>No hay usuarios registrados.</td></tr>";
                    }
                  ?>
                </tbody>
              </table>
            </div>
          </div>

          
          <div class="card-footer text-muted text-center small">
            <i class="fa fa-lock"></i> Sistema exclusivo para uso de las aldeas de Misión Sucre - Municipio Miranda, Falcón.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
