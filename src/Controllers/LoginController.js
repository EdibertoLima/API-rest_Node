const jwt = require('jsonwebtoken');

module.exports={
    async loginvarejao(req,res){
        const token = jwt.sign({
            UserId: "1"
        },"key_teste"
        );
        return res.json({token:token});
    },
    async loginmacapa(req,res){
        const token = jwt.sign({
            UserId: "1"
        },"key_teste2"
        );
        //console.log(token);
        return res.json({token:token});
    }
    
}

