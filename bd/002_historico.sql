CREATE TABLE IF NOT EXISTS `historico` (
  `id_historico` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_local` int(10) unsigned NOT NULL,
  `data_visita` date NOT NULL,
  `descricao` text NOT NULL,
  `salvo_em` datetime NOT NULL,
  PRIMARY KEY (`id_historico`),
  KEY `id_local` (`id_local`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;


ALTER TABLE `historico`
  ADD CONSTRAINT `historico_ibfk_1` FOREIGN KEY (`id_local`) REFERENCES `local` (`id_local`) ON DELETE CASCADE ON UPDATE CASCADE;
