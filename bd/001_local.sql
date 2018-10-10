CREATE TABLE IF NOT EXISTS `local` (
  `id_local` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `bairro` varchar(200) DEFAULT NULL,
  `cidade` varchar(200) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `descricao` text,
  `salvo_em` datetime NOT NULL,
  `alterado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id_local`),
  UNIQUE KEY `ak_historico_lat_lon` (`latitude`,`longitude`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;