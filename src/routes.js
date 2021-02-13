const express = require('express');

const concatController = require("./Controllers/ContactController");
const concatObj = new concatController();

const routes = express.Router();

routes.post('/cadastro',concatObj.addContactMacapa);


module.exports=routes;