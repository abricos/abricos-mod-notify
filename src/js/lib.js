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
        initializer: function(){
            var instance = this;
            this.ownerBaseList(function(){
                NS.roles.load(function(){
                    instance.initCallbackFire();
                });
            }, this);
        }
    }, [], {
        ATTRS: {
            isLoadAppStructure: {value: true},
            Owner: {value: NS.Owner},
            OwnerList: {value: NS.OwnerList},
            Subscribe: {value: NS.Subscribe},
            SubscribeList: {value: NS.SubscribeList},
            Config: {value: NS.Config},
            ownerList: {
                readOnly: true,
                getter:function(){
                    return this.get('ownerBaseList');
                }
            }
        },
        REQS: {
            ownerBaseList: {
                attribute: true,
                type: 'modelList:OwnerList',
            },
            ownerList: {
                attribute: false,
                type: 'modelList:OwnerList',
                onResponse: function(ownerList){
                    var ownerBaseList = this.get('ownerList'),
                        ownerid;

                    ownerList.each(function(owner){
                        ownerid = owner.get('id');
                        if (ownerBaseList.getById(ownerid)){
                            ownerBaseList.removeById(ownerid);
                        }
                        ownerBaseList.add(owner);
                    }, this);
                }
            },
            subscribeList: {
                args: ['module'],
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