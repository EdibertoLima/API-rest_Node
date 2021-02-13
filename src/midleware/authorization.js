const jwt = require('jsonwebtoken');

exports.varejao=(req,res,next)=>{
    try {
        const token = req.headers.authorization.split(' ')[1];
        const decode = jwt.verify(token,"key_teste");
        next();
    } catch (error) {
        return res.status(401).send({mensagem:"erro na autenticação"});
    }
}

exports.macapa=(req,res,next)=>{
    try {
        const token = req.headers.authorization.split(' ')[1];
        const decode = jwt.verify(token,"key_teste2");
        next();
    } catch (error) {
        return res.status(401).send({mensagem:"erro na autenticação"});
    }
}