const express = require('express');

const concatController = require("./Controllers/ContactController");
const createTable = require("./Controllers/CreateTablesController");
const login = require("./Controllers/LoginController");

const auth = require('./midleware/authorization');

const routes = express.Router();

//adicionar contato
routes.post('/addcadastrovarejao',auth.varejao,concatController.addContactVarejao);
routes.post('/addcadastromacapa',auth.macapa,concatController.addContactMacapa);


routes.post('/loginvarejao',createTable.createTableVarejao,login.loginvarejao);
routes.post('/loginmacapa',createTable.createTableMacapa,login.loginmacapa);




module.exports=routes;