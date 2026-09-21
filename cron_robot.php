<?php
/**
 * Cron job para ejecutar el robot diariamente
 * Configurar en Render: Settings > Cron Jobs > Add Cron Job
 * Comando: php cron_robot.php
 * Horario: 0 6 * * * (cada día a las 6:00 AM UTC = 2:00 AM RD)
 */

require_once 'robot_noticias.php';