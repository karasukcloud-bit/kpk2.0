package ru.kpk.attendance;

import android.webkit.CookieManager;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

import java.util.Iterator;

@CapacitorPlugin(name = "SessionCookie")
public class SessionCookiePlugin extends Plugin {

    @PluginMethod
    public void setCookies(PluginCall call) {
        String url = call.getString("url");
        JSObject cookies = call.getObject("cookies");
        if (url == null || url.isEmpty() || cookies == null) {
            call.reject("url and cookies required");
            return;
        }

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);

        Iterator<String> keys = cookies.keys();
        while (keys.hasNext()) {
            String key = keys.next();
            if (key == null || key.isEmpty()) {
                continue;
            }
            String value = cookies.getString(key);
            if (value == null) {
                continue;
            }
            cookieManager.setCookie(
                url,
                key + "=" + value + "; Path=/; Max-Age=2592000; SameSite=Lax"
                    + (url.startsWith("https") ? "; Secure" : "")
            );
        }
        cookieManager.flush();
        call.resolve();
    }

    @PluginMethod
    public void flush(PluginCall call) {
        CookieManager.getInstance().flush();
        call.resolve();
    }
}
