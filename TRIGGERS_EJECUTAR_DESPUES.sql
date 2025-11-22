-- ============================================
-- TRIGGERS AUTOMÁTICOS - PARTE 2
-- Ejecuta este archivo DESPUÉS del anterior
-- ============================================
-- INSTRUCCIONES:
-- 1. Primero ejecuta: EJECUTAR_EN_PHPMYADMIN.sql
-- 2. Luego ejecuta este archivo
-- ============================================

-- Trigger para actualizar status de pólizas automáticamente
DROP TRIGGER IF EXISTS `update_policy_status_on_expiry`;

DELIMITER $$
CREATE TRIGGER `update_policy_status_on_expiry`
BEFORE UPDATE ON `policies`
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin_vigencia < CURDATE() AND NEW.status = 'vigente' THEN
        SET NEW.status = 'vencida';
    ELSEIF DATEDIFF(NEW.fecha_fin_vigencia, CURDATE()) <= 30 AND NEW.status = 'vigente' THEN
        SET NEW.status = 'por_vencer';
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar status de pagos automáticamente
DROP TRIGGER IF EXISTS `update_payment_status_on_overdue`;

DELIMITER $$
CREATE TRIGGER `update_payment_status_on_overdue`
BEFORE UPDATE ON `payments`
FOR EACH ROW
BEGIN
    IF NEW.fecha_vencimiento < CURDATE() AND NEW.status = 'pendiente' THEN
        SET NEW.status = 'vencido';
    END IF;
END$$
DELIMITER ;

-- ============================================
-- FIN DE TRIGGERS
-- ============================================
