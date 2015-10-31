var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    SYS.Application.build(COMPONENT, {}, {
        ownerCreate: function(module, type, ownerid){
            var Owner = this.get('Owner');

            if (Y.Lang.isObject(module)){
                return new Owner(Y.merge(module, {appInstance: this}));
            }

            var owner = new Owner({appInstance: this});
            owner.set('module', module);
            owner.set('type', type);
            owner.set('ownerid', ownerid);
            return owner;
        },
        initializer: function(){
            var instance = this;
            this.appStructure(function(){
                NS.roles.load(function(){
                    instance.initCallbackFire();
                });
            }, this);
        }
    }, [], {
        ATTRS: {
            Owner: {value: NS.Owner},
            Subscribe: {value: NS.Subscribe},
            SubscribeList: {value: NS.SubscribeList},
            Config: {value: NS.Config}
        },
        REQS: {
            subscribeList: {
                attribute: true,
                type: 'modelList:SubscribeList',
            },
            config: {
                attribute: true,
                type: 'model:Config'
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
        }
    });

};