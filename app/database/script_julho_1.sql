-- Cria uma tabela temporária para armazenar os registros únicos
CREATE TABLE double_sinal_temp AS
SELECT * FROM double_sinal
WHERE 1 = 0;

-- Insere os registros únicos na tabela temporária
INSERT INTO double_sinal_temp
SELECT * FROM double_sinal
GROUP BY id_referencia;

-- Cria uma tabela temporária para armazenar os registros únicos
CREATE TABLE double_sinal_temp_2 AS
SELECT * FROM double_sinal
WHERE 1 = 0;

-- Insere os registros únicos na tabela temporária
INSERT INTO double_sinal_temp_2
SELECT * FROM double_sinal_temp
GROUP BY id;

ALTER TABLE `double_sinal_temp_2`
	ADD PRIMARY KEY (`id`),
	ADD UNIQUE INDEX `plataforma_id_id_referencia` (`plataforma_id`, `id_referencia`);

ALTER TABLE `double_sinal_temp_2`
	CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT FIRST;

ALTER TABLE `double_sinal_temp_2`
	ADD CONSTRAINT `FK_double_sinal_double_plataforma` FOREIGN KEY (`plataforma_id`) REFERENCES `double_plataforma` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

-- Renomeie a tabela original (por segurança, caso algo dê errado)
RENAME TABLE double_sinal TO double_sinal_backup;

-- Renomeie a tabela temporária para o nome da tabela original
RENAME TABLE double_sinal_temp_2 TO double_sinal;