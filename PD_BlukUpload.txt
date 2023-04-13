using System;
using System.Configuration;
using System.Collections;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Printing;
using System.Globalization;
using System.Text;
using System.Web.Services;
using System.Data;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Xml.Linq;
using System.Data.OleDb;
using System.IO;
using System.Data.SqlClient;

namespace PCDTermination_BU
{
    public partial class main : System.Web.UI.Page, IPostBackEventHandler
    {
        int count = 0;
        int success = 0;
        int duplicate = 0;
        int error = 0;
        int noup = 0;
        int notfound = 0;

        DateTime dTnow = DateTime.Now;


        protected void Page_Load(object sender, EventArgs e)
        {
            if (!Page.IsPostBack)
            {
                if (!string.IsNullOrEmpty((string)Session["uid"]))
                {
                    lbl_staff.Text = Session["uid"].ToString();
                }
                else
                {
                    Response.Redirect("Login.aspx");
                }

            }
        }
        protected void LinkButton1_Click(object sender, EventArgs e)
        {
            try
            {

                if (!string.IsNullOrEmpty((string)Session["uid"]))
                {
                    if (FileUpload1.HasFile)
                    {

                        string fileName = Path.GetFileName(FileUpload1.PostedFile.FileName);
                        string extension = System.IO.Path.GetExtension(fileName);
                        string filepath = Server.MapPath(fileName);
                        FileUpload1.SaveAs(filepath);

                        if (fileName.Length > 50)
                        {
                            Response.Write("<script>alert('Filename too long.(Within 45 characters, include spaces)');</script>");
                            clearform();
                            File.Delete(filepath);
                            return;
                        }

                        if ((extension == ".xlsx") || (extension == ".xls"))
                        {
                            // Response.Write("<script>alert('Record checking. Please wait...');</script>");                        
                            LoadDataFromExcelToGV(filepath, ".xls", "yes");
                            File.Delete(filepath);

                        }
                        else
                        {
                            Response.Write("<script>alert('Please select Ecxcel File (.xls or .xlsx) .');</script>");
                            clearform();
                            File.Delete(filepath);
                        }



                    }
                    else
                    {
                        clearform();
                        lbl_file.Text = "";
                        lbl_uptime.Text = "";
                        lbl_notfound.Text = "";
                        lbl_filename.Text = "Please select the file.";
                        lbl_message.Visible = false;
                    }

                }
                else
                {
                    Response.Redirect("Login.aspx");
                }
            }
            catch
            {

            }
        }



        public void LoadDataFromExcelToGV(string fpath, string extenion, string hdr)
        {
            if (!string.IsNullOrEmpty((string)Session["uid"]))
            {
                clearform();
                lbl_uptime.Visible = true;
                lbl_message.Visible = true;
                showbox.Visible = true;
                string con = ConfigurationManager.ConnectionStrings["excelcon"].ConnectionString;
                con = String.Format(con, fpath, hdr);
                OleDbConnection excelcon = new OleDbConnection(con);
                excelcon.Open();
                DataTable exceldta = excelcon.GetOleDbSchemaTable(OleDbSchemaGuid.Tables, null);
                string excelsheetname = exceldta.Rows[0]["TABLE_NAME"].ToString();
                OleDbCommand selectcommand = new OleDbCommand("Select * from[" + excelsheetname + "]", excelcon);
                OleDbDataAdapter da = new OleDbDataAdapter(selectcommand);
                DataTable dt = new DataTable();
                DataSet ds = new DataSet();
                da.Fill(dt);
                da.Fill(ds);
                excelcon.Close();

                GridView1.DataSource = dt;
                GridView1.DataBind();

                string fileName = Path.GetFileName(FileUpload1.PostedFile.FileName).Trim();
                lbl_file.Text = "File Name: ";
                lbl_filename.Text = fileName;
                File.Delete(fpath);
                checkRec(fpath);



            }

            else
            {
                Response.Redirect("Login.aspx");
            }
        }


        public void checkRec(string fpath)
        {
            string dT = dTnow.ToString("yyyy-MM-dd");
            string ckdT = dTnow.AddYears(-2).ToString("yyyy-MM-dd");

            string sqlcon = ConfigurationManager.ConnectionStrings["LTS_Conn_UAT"].ConnectionString;
            SqlConnection scon = new SqlConnection(sqlcon);

            for (int i = 0; i < GridView1.Rows.Count; i++)
            {
                string sdate = dTnow.AddYears(-2).ToString("yyyy-MM-dd") + " 00:00:00.000";
                string edate = dTnow.ToString("yyyy-MM-dd") + " 23:59:59.999";

                count++;
                string teamname = GridView1.Rows[i].Cells[3].Text.Trim();

                string sqlquery = "SELECT * FROM items_teamno where teamno = '" + teamname + "'AND active = 1";
                scon.Open();
                SqlCommand sqlcmd = new SqlCommand(sqlquery, scon);
                DataTable sqldt = new DataTable();
                SqlDataAdapter sqlda = new SqlDataAdapter(sqlcmd);
                sqlda.Fill(sqldt);

                string sqlquery1 = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text.Trim() + "'AND assigntoagentid is not null AND CASESTATUS != 'NEW'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                SqlCommand sqlcmd1 = new SqlCommand(sqlquery1, scon);
                DataTable sqldt1 = new DataTable();
                SqlDataAdapter sqlda1 = new SqlDataAdapter(sqlcmd1);
                sqlda1.Fill(sqldt1);


                if (GridView1.Rows[i].Cells[6].Text.ToUpper().Trim() != "ASSIGN" || GridView1.Rows[i].Cells[0].Text.Trim() == "" || sqldt.Rows.Count == 0 || sqldt1.Rows.Count != 0)
                {
                    error++;
                    noup++;
                    GridView1.Rows[i].BackColor = System.Drawing.Color.Red;
                    scon.Close();

                    var s = "if(confirm('Duplicate/Error found, Continue?')){0};else {1};";
                    ScriptManager.RegisterStartupScript(this, typeof(main), "", string.Format(s, this.ClientScript.GetPostBackEventReference(this, "ok"), this.ClientScript.GetPostBackEventReference(this, "no")), true);

                }
                else
                {
                   
                    scon.Close();
                    string query_found = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text.Trim() + "'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                    scon.Open();
                    SqlCommand cmd_found = new SqlCommand(query_found, scon);
                    DataTable dtfound = new DataTable();
                    SqlDataAdapter dafound = new SqlDataAdapter(cmd_found);
                    dafound.Fill(dtfound);

                    if (dtfound.Rows.Count == 0)
                    {
                        GridView1.Rows[i].BackColor = System.Drawing.Color.CornflowerBlue;
                        notfound++;
                        noup++;
                        scon.Close();

                        var s = "if(confirm('Duplicate/Error found, Continue?')){0};else {1};";
                        ScriptManager.RegisterStartupScript(this, typeof(main), "", string.Format(s, this.ClientScript.GetPostBackEventReference(this, "ok"), this.ClientScript.GetPostBackEventReference(this, "no")), true);

                    }
                    else
                    {
                        scon.Close();
                        string query_sear = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text.Trim() + "'AND loginid is not null AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                        scon.Open();
                        SqlCommand cmd_sear = new SqlCommand(query_sear, scon);
                        DataTable dtser = new DataTable();
                        SqlDataAdapter daser = new SqlDataAdapter(cmd_sear);
                        daser.Fill(dtser);

                        if (dtser.Rows.Count != 0)
                        {
                            GridView1.Rows[i].BackColor = System.Drawing.Color.Gold;
                            scon.Close();
                            duplicate++;
                            noup++;

                            var s = "if(confirm('Duplicate/Error found, Continue?')){0};else {1};";
                            ScriptManager.RegisterStartupScript(this, typeof(main), "", string.Format(s, this.ClientScript.GetPostBackEventReference(this, "ok"), this.ClientScript.GetPostBackEventReference(this, "no")), true);
                        }
                        else
                        {
                            var s = "if(confirm('No duplicate/Error found, Continue?')){0};else {1};";
                            ScriptManager.RegisterStartupScript(this, typeof(main), "", string.Format(s, this.ClientScript.GetPostBackEventReference(this, "ok"), this.ClientScript.GetPostBackEventReference(this, "no")), true);
                        }
                    }
                }
            }
        }


        public void upDateRec()
        {

            if (!string.IsNullOrEmpty((string)Session["uid"]))
            {
                lbl_message.Visible = false;
                string dT = dTnow.ToString("yyyy-MM-dd");
                string ckdT = dTnow.AddYears(-2).ToString("yyyy-MM-dd");
                string fileName = lbl_filename.Text;

                string sqlcon = ConfigurationManager.ConnectionStrings["LTS_Conn_UAT"].ConnectionString;
                SqlConnection scon = new SqlConnection(sqlcon);

                for (int i = 0; i < GridView1.Rows.Count; i++)
                
                {
                    string sdate = dTnow.AddYears(-2).ToString("yyyy-MM-dd") + " 00:00:00.000";
                    string edate = dTnow.ToString("yyyy-MM-dd") + " 23:59:59.999";

                    count++;
                    string teamname = GridView1.Rows[i].Cells[3].Text.Trim();

                    string sqlquery = "SELECT * FROM items_teamno where teamno = '" + teamname + "'AND active = 1";
                    scon.Open();
                    SqlCommand sqlcmd = new SqlCommand(sqlquery, scon);
                    DataTable sqldt = new DataTable();
                    SqlDataAdapter sqlda = new SqlDataAdapter(sqlcmd);
                    sqlda.Fill(sqldt);

                    string sqlquery1 = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text.Trim() + "'AND assigntoagentid is not null AND CASESTATUS != 'NEW'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                    SqlCommand sqlcmd1 = new SqlCommand(sqlquery1, scon);
                    DataTable sqldt1 = new DataTable();
                    SqlDataAdapter sqlda1 = new SqlDataAdapter(sqlcmd1);
                    sqlda1.Fill(sqldt1);


                    if (GridView1.Rows[i].Cells[6].Text.ToUpper().Trim() != "ASSIGN" || GridView1.Rows[i].Cells[0].Text.Trim() == "" || sqldt.Rows.Count == 0 || sqldt1.Rows.Count != 0)
                    {
                        error++;
                        noup++;

                        scon.Close();
                        if (sqldt1.Rows.Count != 0)
                        {
                            DateTime assDate = DateTime.Parse(sqldt1.Rows[0][18].ToString());
                            string errorquery = "Insert into errorLog(serial,filename,teamname,status,uploadDate,casestatus,teamassignid,teamassigndt,assigntoagentid) values('" + GridView1.Rows[i].Cells[0].Text + "','" + fileName + "','" + teamname + "','" + GridView1.Rows[i].Cells[6].Text + "','" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "','" + sqldt1.Rows[0][7].ToString() + "','" + sqldt1.Rows[0][17].ToString() + "','" + assDate.ToString("yyyy-MM-dd HH:mm:ss.fff") + "','" + sqldt1.Rows[0][22].ToString() + "')";
                            scon.Open();
                            SqlCommand cmd_err = new SqlCommand(errorquery, scon);
                            cmd_err.ExecuteNonQuery();
                            scon.Close();
                        }
                        else
                        {
                            string errorquery1 = "Insert into errorLog(serial,filename,teamname,status,uploadDate) values('" + GridView1.Rows[i].Cells[0].Text + "','" + fileName + "','" + teamname + "','" + GridView1.Rows[i].Cells[6].Text + "','" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "')";
                            scon.Open();
                            SqlCommand cmd_err1 = new SqlCommand(errorquery1, scon);
                            cmd_err1.ExecuteNonQuery();
                            scon.Close();
                        }

                    }
                    else
                    {
                       
                        scon.Close();
                        string query_found = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text + "'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                        scon.Open();
                        SqlCommand cmd_found = new SqlCommand(query_found, scon);
                        DataTable dtfound = new DataTable();
                        SqlDataAdapter dafound = new SqlDataAdapter(cmd_found);
                        dafound.Fill(dtfound);

                        if (dtfound.Rows.Count == 0)
                        {
                            notfound++;
                            noup++;
                            scon.Close();
                            string query_no = "Insert into casenotfound(serial,filename,uploadDate) values('" + GridView1.Rows[i].Cells[0].Text + "','" + fileName + "','" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "')";
                            scon.Open();
                            SqlCommand cmd_no = new SqlCommand(query_no, scon);
                            cmd_no.ExecuteNonQuery();
                            scon.Close();



                        }
                        else
                        {
                            scon.Close();
                            string query_sear = "SELECT * FROM Faxpcdterm where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text + "'AND loginid is not null AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                            scon.Open();
                            SqlCommand cmd_sear = new SqlCommand(query_sear, scon);
                            DataTable dtser = new DataTable();
                            SqlDataAdapter daser = new SqlDataAdapter(cmd_sear);
                            daser.Fill(dtser);


                            if (dtser.Rows.Count != 0)
                            {
                                

                                string viind = GridView1.Rows[i].Cells[1].Text.Trim();
                                string tvnind = GridView1.Rows[i].Cells[2].Text.Trim();
                                string supremark = GridView1.Rows[i].Cells[7].Text;

                                if (viind != "Y")
                                {
                                    viind = "";
                                }
                                if (tvnind != "Y")
                                {
                                    tvnind = "";
                                }
                                if (supremark == "&nbsp;")
                                {
                                    supremark = "";
                                }

                                scon.Close();
                                string query1 = "Insert into duplicateLog(serial,filename,uploadDate,casestatus,teamassignid,teamassigndt,assigntoagentid,loginid) values('" + GridView1.Rows[i].Cells[0].Text + "','" + fileName + "','" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "','" + dtser.Rows[0][7].ToString() + "','" + dtser.Rows[0][17].ToString() + "','" + dtser.Rows[0][18].ToString() + "','" + dtser.Rows[0][22].ToString() + "','" + dtser.Rows[0][11].ToString() + "')";
                                scon.Open();
                                SqlCommand cmd1 = new SqlCommand(query1, scon);
                                cmd1.ExecuteNonQuery();
                                scon.Close();
                                duplicate++;


                                string dquery = "UPDATE Faxpcdterm SET supassignid = '" + Session["uid"].ToString() + "',supassigndt = '" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "',teamname = '" + GridView1.Rows[i].Cells[3].Text + "',FSA = '" + GridView1.Rows[i].Cells[4].Text + "',loginid = '" + GridView1.Rows[i].Cells[5].Text + "',CASESTATUS = '" + GridView1.Rows[i].Cells[6].Text.ToUpper() + "',supremark = '" + supremark + "',crdate = '" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "',filename = '" + fileName + "',viind = '" + viind + "',tvnind = '" + tvnind + "' where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text + "'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                                scon.Open();
                                SqlCommand dcmd = new SqlCommand(dquery, scon);
                                dcmd.ExecuteNonQuery();
                                scon.Close();
                                success++;



                            }
                            else
                            {
                               
                                string viind = GridView1.Rows[i].Cells[1].Text.Trim();
                                string tvnind = GridView1.Rows[i].Cells[2].Text.Trim();
                                string supremark = GridView1.Rows[i].Cells[7].Text;

                                if (viind != "Y")
                                {
                                    viind = "";
                                }
                                if (tvnind != "Y")
                                {
                                    tvnind = "";
                                }
                                if (supremark == "&nbsp;")
                                {
                                    supremark = "";
                                }

                                scon.Close();
                                string query = "UPDATE Faxpcdterm SET supassignid = '" + Session["uid"].ToString() + "',supassigndt = '" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "',teamname = '" + GridView1.Rows[i].Cells[3].Text + "',FSA = '" + GridView1.Rows[i].Cells[4].Text + "',loginid = '" + GridView1.Rows[i].Cells[5].Text + "',CASESTATUS = '" + GridView1.Rows[i].Cells[6].Text.ToUpper() + "',supremark = '" + supremark + "',crdate = '" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "',filename = '" + fileName + "',viind = '" + viind + "',tvnind = '" + tvnind + "' where SERIALNO = '" + GridView1.Rows[i].Cells[0].Text + "'AND RECCREATE_DT >'" + sdate + "'AND RECCREATE_DT <'" + edate + "'";
                                scon.Open();
                                SqlCommand cmd = new SqlCommand(query, scon);
                                cmd.ExecuteNonQuery();
                                scon.Close();
                                success++;



                            }

                        }
                    }
                }

                scon.Close();
                string staffno = Session["uid"].ToString();
                string uploadquery = "Insert into uploadrecordlog(uid,filename,uploadDate,totalRecord,success,duplicate,error,notfound) values ('" + staffno + "','" + fileName.ToString() + "','" + dTnow.ToString("yyyy-MM-dd HH:mm:ss.fff") + "','" + count.ToString() + "','" + success.ToString() + "','" + duplicate.ToString() + "','" + error.ToString() + "','" + notfound.ToString() + "')";
                scon.Open();
                SqlCommand cmd3 = new SqlCommand(uploadquery, scon);
                cmd3.ExecuteNonQuery();
                scon.Close();

                DateTime dTstop = DateTime.Now;
                TimeSpan ts = dTstop - dTnow;
                int seconds = Convert.ToInt32(ts.TotalSeconds);
                TimeSpan totaltime = new TimeSpan(0, 0, seconds);
                if (totaltime.Minutes < 1)
                {
                    lbl_uptime.Text = "Upload time: " + totaltime.Minutes + " min " + totaltime.Seconds + " sec";
                }
                else
                {
                    lbl_uptime.Text = "Upload time: " + totaltime.Minutes + " mins " + totaltime.Seconds + " sec";
                }

                if (duplicate != 0 || error != 0 || notfound != 0)
                {

                    lbl_duplicate.BackColor = System.Drawing.Color.Gold;
                    lbl_error.BackColor = System.Drawing.Color.Red;
                    lbl_notfound.BackColor = System.Drawing.Color.CornflowerBlue;
                    lbl_totalRe.Text = "Total Record: " + count.ToString();
                    lbl_success.Text = "Success:" + success.ToString();
                    lbl_duplicate.Text = "Duplicate Record:" + duplicate.ToString();
                    lbl_error.Text = "Error Record:" + error.ToString();
                    lbl_notfound.Text = "Serial not found:" + notfound.ToString();
                    lbl_notupdate.Text = "Record has not been updated:" + noup.ToString();


                }
                else
                {
                    lbl_duplicate.BackColor = System.Drawing.Color.White;
                    lbl_totalRe.Text = "Total Record: " + count.ToString();
                    lbl_success.Text = "Success:" + success.ToString();
                    lbl_duplicate.Text = "Upload successfully!";


                }
            }
            else
            {
                Response.Redirect("Login.aspx");
            }



        }


        protected void LinkButton3_Click(object sender, EventArgs e)
        {
            clearform();
            lbl_message.Visible = false;

        }

        protected void LinkButton4_Click(object sender, EventArgs e)
        {
            Session.Clear();
            Response.Redirect("Login.aspx");

        }


        public void clearform()
        {
            GridView1.DataSource = null;
            GridView1.DataBind();

            lbl_duplicate.Text = "";
            lbl_totalRe.Text = "";
            lbl_file.Text = "";
            lbl_filename.Text = "";
            lbl_error.Text = "";
            lbl_success.Text = "";
            lbl_notupdate.Text = "";
            lbl_notfound.Text = "";
            lbl_uptime.Text = "";

        }


        public void RaisePostBackEvent(string eventArgument)
        {
            switch (eventArgument)
            {
                case "ok":
                    upDateRec();
                    break;
                case "no":
                    ScriptManager.RegisterClientScriptBlock(this, this.GetType(), "alertMessage", "alert('File upload has been cancelled.')", true);
                    clearform();
                    lbl_message.Visible = false;
                    break;
            }
        }

    }
}