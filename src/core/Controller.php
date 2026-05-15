<?php
namespace LEX360\Core;

use LEX360\Models\Dao\UsuarioDao;
use LEX360\Models\Dao\CarteraDao;
use LEX360\Models\Dao\ClienteDao;
use LEX360\Models\Dao\AsistenciaDao;
use LEX360\Models\Dao\TipologiaDao;
use LEX360\Models\Dao\HistorialDao;
use LEX360\Models\Dao\PagoDao;
use LEX360\Models\Dao\PromesaDao;
use LEX360\Models\Dao\Db\Database;
use PDO;

abstract class Controller
{
    protected Session $session;
    protected UsuarioDao $usuarioDao;
    protected CarteraDao $carteraDao;
    protected ClienteDao $clienteDao;
    protected AsistenciaDao $asistenciaDao;
    protected TipologiaDao $tipologiaDao;
    protected HistorialDao $historialDao;
    protected PagoDao $pagoDao;
    protected PromesaDao $promesaDao;
    protected PDO $db;

    public function __construct()
    {
        $this->session = new Session();
        $this->usuarioDao = new UsuarioDao();
        $this->carteraDao = new CarteraDao();
        $this->clienteDao = new ClienteDao();
        $this->asistenciaDao = new AsistenciaDao();
        $this->tipologiaDao = new TipologiaDao();
        $this->historialDao = new HistorialDao();
        $this->pagoDao = new PagoDao();
        $this->promesaDao = new PromesaDao();
        $this->db = Database::getInstance();
    }
}