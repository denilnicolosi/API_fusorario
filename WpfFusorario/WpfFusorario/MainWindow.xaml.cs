using Json.Net;
using RestSharp;
using RestSharp.Authenticators;
using System;
using System.Collections.Generic;
using System.Windows;

namespace WpfFusorario
{
    /// <summary>
    /// Logica di interazione per MainWindow.xaml
    /// </summary>
    public partial class MainWindow : Window
    {
        private Dictionary<string, object> resp;

        public MainWindow()
        {
            InitializeComponent();
            resp = new Dictionary<string, object>();
        }

        private void BtnZone_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                var client = new RestClient("https://api-fusorario.herokuapp.com");
                client.Authenticator = new HttpBasicAuthenticator("username", "pippo");
                var request = new RestRequest("/timezone", Method.GET);
                request.AddParameter("timezone", zone.Text);
                IRestResponse response = client.Execute(request);

                var content = response.Content;
                if (response.StatusCode == System.Net.HttpStatusCode.OK && !content.Contains("error"))
                {
                    var resp = JsonNet.Deserialize<Dictionary<string, object>>(content);

                    //composizione output
                    timezone.Text = "Numero della settimana : " + resp["week_number"].ToString();
                    timezone.Text += "\r Giorno dell'anno : " + resp["day_of_year"].ToString();
                    timezone.Text += "\r Giorno della settimana : " + resp["day_of_week"].ToString();
                    timezone.Text += "\r UTC : " + resp["utc_offset"].ToString();
                    timezone.Text += "\r Data : " + resp["date"].ToString();
                    timezone.Text += "\r Ora : " + resp["time"].ToString();
                    timezone.Text += "\r Zona di fuso orario : " + resp["timezone"].ToString();
                }
                else
                    timezone.Text = "Errore";
            }
            catch (Exception ex)
            {
                Console.WriteLine(ex);
            }
        }


        private void BtnIp_Click(object sender, RoutedEventArgs e)
        {
            try
            {             
                var client = new RestClient("https://api-fusorario.herokuapp.com");
                client.Authenticator= new HttpBasicAuthenticator("username", "pippo");
                var request = new RestRequest("/ip", Method.GET);
                request.AddParameter("ip", ip.Text);
                IRestResponse response = client.Execute(request);

                var content = response.Content;
                if (response.StatusCode == System.Net.HttpStatusCode.OK && !content.Contains("error"))
                {
                    var resp = JsonNet.Deserialize<Dictionary<string, object>>(content);

                    //composizione output
                    timezone.Text = " Ip : " + resp["ip"].ToString();
                    timezone.Text += "\r Numero della settimana : " + resp["week_number"].ToString();
                    timezone.Text += "\r Giorno dell'anno : " + resp["day_of_year"].ToString();
                    timezone.Text += "\r Giorno della settimana : " + resp["day_of_week"].ToString();
                    timezone.Text += "\r UTC : " + resp["utc_offset"].ToString();
                    timezone.Text += "\r Data : " + resp["date"].ToString();
                    timezone.Text += "\r Ora : " + resp["time"].ToString();
                    timezone.Text += "\r Zona di fuso orario : " + resp["timezone"].ToString();
                }
                else
                    timezone.Text = "Errore";
            }
            catch (Exception ex)
            {
                Console.WriteLine(ex);
            }
        }
    }
}
