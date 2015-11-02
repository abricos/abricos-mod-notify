var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Owner = Y.Base.create('owner', SYS.AppModel, [], {
        structureName: 'Owner',
        isEnable: function(){
            var parent = this, val;
            while (parent){
                val = parent.get('status') === NS.Owner.STATUS_ON;
                if (!val){
                    break;
                }
                parent = parent.get('parent');
            }
            return val;
        }
    }, {
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
        ATTRS: {
            parent: {
                readOnly: true,
                getter: function(val){
                    if (Y.Lang.isUndefined(val)){
                        var parentid = this.get('parentid');
                        if (parentid === 0){
                            val = null;
                        } else {
                            val = this.appInstance.get('ownerBaseList').getById(parentid);
                        }
                    }
                    return val;
                }
            }
        },
    });

    NS.OwnerList = Y.Base.create('ownerList', SYS.AppModelList, [], {
        appItem: NS.Owner,
        findOwner: function(options){
            options = Y.merge({
                module: '',
                type: '',
                method: '',
                itemid: 0
            }, options || {});

            var ret = null;
            this.some(function(owner){
                if (options.module === owner.get('module')
                    && options.type === owner.get('type')
                    && options.method === owner.get('method')
                    && options.itemid === owner.get('itemid')){
                    ret = owner;
                    return true;
                }
            }, this);
            return ret;
        }
    });

    NS.Subscribe = Y.Base.create('subscribe', SYS.AppModel, [], {
        structureName: 'Subscribe'
    }, {
        STATUS_UNSET: 'unset',
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
        ATTRS: {
            owner: {
                readOnly: true,
                getter: function(val){
                    if (Y.Lang.isUndefined(val)){
                        var ownerid = this.get('ownerid');
                        val = this.appInstance.get('ownerList').getById(ownerid);
                    }
                    return val;
                }
            }
        }
    });

    NS.SubscribeList = Y.Base.create('subscribeList', SYS.AppModelList, [], {
        appItem: NS.Subscribe,
        getSubscribe: function(options){
            options = Y.merge({
                owner: {},
                subscribe: {}
            }, options || {});

            var app = this.appInstance,
                ownerList = app.get('ownerList'),
                owner = ownerList.findOwner(options.owner);

            if (!owner){
                var parent = ownerList.findOwner(Y.merge(options.owner, {itemid: 0}));
                if (!parent){
                    return null;
                }
                var Owner = app.get('Owner');
                owner = new Owner(Y.merge(options.owner, {
                    appInstance: app,
                    parentid: parent.get('id'),
                    id: NS.SubscribeList._idCounter--
                }));
            }
            var ownerid = owner.get('id'),
                subscribe = this.getBy('ownerid', ownerid);

            if (subscribe){
                return subscribe;
            }
            var Subscribe = app.get('Subscribe');
            subscribe = new Subscribe(Y.merge(options.subscribe, {
                appInstance: app,
                ownerid: ownerid,
                id: NS.SubscribeList._idCounter--
            }));
            return subscribe;
        }
    }, {
        _idCounter: -1
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });

};