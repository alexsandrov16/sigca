<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear procedimiento sp_InsertarUsuario
        DB::statement("
            CREATE PROCEDURE sp_InsertarUsuario
                @exp INT,
                @rol INT,
                @usuario VARCHAR(80)
            AS
            BEGIN
                DECLARE
                    @nombre VARCHAR(150),
                    @cargo VARCHAR(150);

                -- Verificar si el usuario ya existe
                IF EXISTS (SELECT 1 FROM Usuarios WHERE id = @exp)
                BEGIN
                    RAISERROR ('El usuario ya existe', 16, 1);
                END;

                -- Cargar datos de SIGERH
                SELECT @nombre = nombre,
                        @cargo = cargo
                FROM SERVERSQL.sigerh_gx.dbo.v_CargoTrabajadores WHERE expediente = @exp;

                -- Insertar usuarios
                INSERT INTO Usuarios(id, nombre, cargo, rol, usuario) VALUES(@exp, @nombre, @cargo, @rol, @usuario);
            END;
        ");

        // Crear procedimiento sp_ActualizarProductosExistentes
        DB::statement("
            CREATE PROCEDURE sp_ActualizarProductosExistentes
            AS
            BEGIN
                UPDATE ps
                SET ps.cant_recibida = rpc.Cantidad_Recibida
                FROM ProductosSolicitud ps 
                INNER JOIN UNE_2316A_INT.dbo.vw_SIGCA_RecepcionProductosContabilizado rpc ON ps.id_producto = rpc.Id_Producto
                INNER JOIN Sync sy ON rpc.Fecha_ent > sy.fecha;
            END;
        ");

        // Crear procedimiento sp_CerrarSolicitud
        DB::statement("
            CREATE PROCEDURE sp_CerrarSolicitud
            AS
            BEGIN
                UPDATE sh
                SET sh.estado = 3
                FROM SolicitudesHistorico sh
                INNER JOIN ProductosSolicitud ps ON ps.id_solicitud = sh.id_solicitud
                WHERE ps.cant_solicitada = ps.cant_recibida;
            END;
        ");

        // Crear procedimiento sp_UltimaSincronizacion
        DB::statement("
            CREATE PROCEDURE sp_UltimaSincronizacion
            AS
            BEGIN
                DELETE FROM Sync;
                INSERT INTO Sync VALUES(DEFAULT);
            END;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar procedimientos almacenados si existen
        DB::statement("DROP PROCEDURE IF EXISTS sp_InsertarUsuario;");
        DB::statement("DROP PROCEDURE IF EXISTS sp_ActualizarProductosExistentes;");
        DB::statement("DROP PROCEDURE IF EXISTS sp_CerrarSolicitud;");
        DB::statement("DROP PROCEDURE IF EXISTS sp_UltimaSincronizacion;");
    }
};