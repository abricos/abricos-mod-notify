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
            // this.ownerBaseList(function(){
            NS.roles.load(function(){
                instance.initCallbackFire();
            });
            // }, this);
        },
        registerOwner: function(owner){
            if (!owner || !Y.Lang.isFunction(owner.get) || owner.get('isBase')){
                return;
            }
            var ownerList = this.get('ownerList');
            if (!ownerList){
                return;
            }
            var id = owner.get('id');
            if (ownerList.getById(id)){
                return;
            }
            ownerList.add(owner);
        },
        registerOwnerList: function(list){
            if (!list || !Y.Lang.isFunction(list.each)){
                return;
            }
            list.each(function(owner){
                this.registerOwner(owner);
            }, this);
        },
        registerSubscribe: function(subscribe){
            if (!subscribe || !Y.Lang.isFunction(subscribe.get)){
                return;
            }
            var subscribeList = this.get('subscribeList');
            if (!subscribeList){
                return;
            }
            var id = subscribe.get('id');
            if (subscribeList.getById(id)){
                return;
            }
            subscribeList.add(subscribe);
        },
        registerSubscribeList: function(list){
            if (!list || !Y.Lang.isFunction(list.each)){
                return;
            }
            list.each(function(subscribe){
                this.registerSubscribe(subscribe);
            }, this);
        },
        summaryUpdate: function(){
            if (Brick.env.user.id === 0){
                return;
            }
            this.summaryList(function(err, result){
            }, this);
        },
        cronIsStart: function(){
            return !!this._cronThread;
        },
        cronStart: function(){
            if (this.cronIsStart()){
                return;
            }
            this.summaryUpdate();

            var instance = this;
            this._cronThread = setInterval(function(){
                try {
                    instance.summaryUpdate.call(instance);
                } catch (e) {
                }
            }, 1000 * 60 * 5);
        },
        cronStop: function(){
            if (!this.cronIsStart()){
                return;
            }
            clearInterval();
        }
    }, [], {
        ATTRS: {
            isLoadAppStructure: {value: true},
            Owner: {value: NS.Owner},
            OwnerList: {value: NS.OwnerList},
            Subscribe: {value: NS.Subscribe},
            SubscribeList: {value: NS.SubscribeList},
            Summary: {value: NS.Summary},
            SummaryList: {value: NS.SummaryList},
            Config: {value: NS.Config},
            ownerList: {
                readOnly: true,
                getter: function(){
                    return this.get('ownerBaseList');
                }
            },
            subscribeList: {
                readOnly: true,
                getter: function(){
                    return this.get('subscribeBaseList');
                }
            }
        },
        REQS: {
            ownerBaseList: {
                attribute: true,
                type: 'modelList:OwnerList',
            },
            subscribeBaseList: {
                attribute: true,
                type: 'modelList:SubscribeList',
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
            subscribeSave: {
                args: ['subscribe'],
                type: 'model:Subscribe',
            },
            summaryList: {
                attach: 'ownerBaseList,subscribeBaseList',
                type: 'modelList:SummaryList',
            },
            config: {
                attribute: true,
                type: 'model:Config'
            },
            configSave: {
                args: ['config']
            },
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            config: {
                view: function(){
                    return this.getURL('ws') + 'config/ConfigWidget/'
                }
            },
        }
    });

};