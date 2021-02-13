const jwt = require('jsonwebtoken');

module.exports={
    async loginvarejao(req,res){
        const token = jwt.sign({
            UserId: "1"
        },process.env.JWT_KEY_varejao
        );
        return res.json({token:token});
    },
    async loginmacapa(req,res){
        const token = jwt.sign({
            UserId: "1"
        },process.env.JWT_KEY_macapa
        );
        //console.log(token);
        return res.json({token:token});
    }
    
}

