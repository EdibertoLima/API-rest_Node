const Sequelize = require("sequelize");
const dbConfig = require("../config/database");

const {ContactMacapa,ContactVarejao} = require("../models/contact");

const conectionMacapa = new Sequelize(dbConfig.macapa);
const conectionVarejao = new Sequelize(dbConfig.varejao);

ContactMacapa.init(conectionMacapa);
ContactVarejao.init(conectionVarejao);



