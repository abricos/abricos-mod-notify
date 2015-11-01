var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;


    var isOwner = function(val){
        if (!val){
            return false;
        }
        if (!Y.Lang.isFunction(val.get)){
            return false;
        }
        return true;
    };

    NS.Owner = Y.Base.create('owner', SYS.AppModel, [], {
        structureName: 'Owner',
        /*
         compare: function(val){
         if (!NS.Owner.isOwner(val)){
         return false;
         }
         return val.get('module') === this.get('module')
         && val.get('type') === this.get('type')
         && val.get('ownerid') === this.get('ownerid');
         }
         /**/
        isEnable: function(){
            console.log('-------------');
            var parent = this.get('parent');
            console.log('!!!!!!!!!!!!!!!!!!!');
            return this.get('status') === NS.Owner.STATUS_ON;
        }
    }, {
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
        ATTRIBUTE: {
            validator: isOwner,
            setter: function(val){
                if (val.module && val.type && val.ownerid){
                    return this.get('appInstance').ownerCreate(val.module, val.type, val.ownerid);
                }
                return val;
            }
        },
        isOwner: isOwner,
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
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
        isUnset: function(subscribe){
            if (!subscribe){
                return NS.Subscribe.STATUS_UNSET;
            }
        },
        isOn: function(subscribe){
            if (!subscribe){
                return false;
            }

        }
    });

    NS.SubscribeList = Y.Base.create('subscribeList', SYS.AppModelList, [], {
        appItem: NS.Subscribe
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });

};