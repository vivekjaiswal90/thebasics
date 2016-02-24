package epark.api.searchshop;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.InetSocketAddress;
import java.net.Proxy;
import java.net.URL;
import java.net.URLEncoder;

import android.content.Context;
import android.os.AsyncTask;
import android.widget.TextView;


public class GetReplyMessage extends AsyncTask<String,Void,String> {
	
	private TextView resVw;
	private Context context;
	//private String access_token, prefecture_id, latitude, longitude, keyword, page;
	
//	public GetReplyMessage(String string, String string2, String string3,
//			String string4, String string5, String string6) {
//		// TODO Auto-generated constructor stub
//	this.access_token = string;
//	this.prefecture_id = string2;
//	this.latitude = string3;
//	this.longitude = string4;
//	this.keyword = string5;
//	this.page = string6;
//	}

	public GetReplyMessage(TextView resVw) {
		// TODO Auto-generated constructor stub
		this.resVw = resVw;
	}

	protected void onPostExecute(String result) {
	    // TODO Auto-generated method stub
	    //super.execute(result);
	    // update textview here
	    //textView.setText("Server message is "+result);
		//Toast.makeText(this, "SUCCESS", Toast.LENGTH_SHORT).show();
		resVw.setText(result);
	}

	@Override
	protected void onPreExecute() {
	    // TODO Auto-generated method stub
	    super.onPreExecute();
	}

	@Override
	protected String doInBackground(String... params) {
	     try
	        {
	    	 //HttpResponse ret;
	    	 	//int proxy = 8650;
	            //HttpClient httpclient = new DefaultHttpClient();
	    	 	String access_token = params[0];
	    	 	String prefecture_id = params[1];
	    	 	String latitude = params[2];
	    	 	String longitude = params[3];
	    	 	String keyword = params[4];
	    	 	String page = params[5];
	    	 	
	    	 	String link = "http://203.131.199.77/api/cardbook/searchshop";
	    	 	
	    	 	String data = URLEncoder.encode("access_token", "UTF-8") + "=" +URLEncoder.encode(access_token, "UTF-8");
	    	 	data += "&" + URLEncoder.encode("prefecture_id", "UTF-8") + "=" +URLEncoder.encode(prefecture_id, "UTF-8");
	    	 	data += "&" + URLEncoder.encode("latitude", "UTF-8") + "=" +URLEncoder.encode(latitude, "UTF-8");
	    	 	data += "&" + URLEncoder.encode("longitude", "UTF-8") + "=" +URLEncoder.encode(longitude, "UTF-8");
	    	 	data += "&" + URLEncoder.encode("keyword", "UTF-8") + "=" +URLEncoder.encode(keyword, "UTF-8");
	    	 	data += "&" + URLEncoder.encode("page", "UTF-8") + "=" +URLEncoder.encode(page, "UTF-8");
	    	 	
	            Proxy proxy = new Proxy(Proxy.Type.HTTP, new InetSocketAddress("proxy.dm-hikari.ad.hikari.co.jp", 8080));

	            URL url = new URL(link);
	            HttpURLConnection conn = (HttpURLConnection)url.openConnection(proxy);
	            conn.setReadTimeout(10000);
	            conn.setConnectTimeout(15000);
	            conn.setRequestMethod("POST");
	            conn.setDoInput(true);
	            conn.setDoOutput(true);
	            int i = 0;
	            i++;
	            OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream());
	            wr.write(data);
	            wr.flush();
	            BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
	            StringBuilder sb = new StringBuilder();
	            String line = null;
	            while((line = reader.readLine()) != null)
	            {
	            	sb.append(line);
	            	break;
	            }
	            return sb.toString(); 
	            
	        }
	         catch(Exception e){
	             return new String("Exception: " + e.getMessage());
	         }

	
}
}
