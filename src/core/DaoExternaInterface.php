<?php
namespace LEX360\Core;

interface DaoExternaInterface
{
    public function findByDpi(String $dpi): array;
    public function findByNombre(String $nombre): array;
    public function findByOtros(String $otros): array;

}