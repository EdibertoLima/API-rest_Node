const express = require('express');
const routes = require('./routes');

const PORT =3000;
const HOST = '0.0.0.0';

require("./database");

const app =express();

app.use(express.json());

app.use(routes);

app.listen(PORT,HOST);