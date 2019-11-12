//login
LOGON_SITE = "https://"+host+"/player-api/identity/CustomerLoginRedir";	

		form_data =
			     new NameValuePair[] {

					new NameValuePair("CustomerID", un),
					new NameValuePair("Password",pw),
					new NameValuePair("Submit.x","40"),
					new NameValuePair("Submit.y","16"),
								};
								
	rsp = PostUnc("https://"+host);
//login end

//get lines page start
//period number below is 0 fg, 1 h1, 2 h2


				LOGON_SITE = "https://"+host+"/player-api/api/wager/schedules/S/0";	//csr20190713
				String jsonleaguedat = "[";		//VERY IMPORTANT FIRST ENTRY DOESN'T START WITH A COMMA!!!!!
				
						
						jsonleaguedat = jsonleaguedat+"{\"IdSport\":\"39\"," +"\"Period\":\""+periodNumber+"\"" +"}"; //cbb 
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"372\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cbb 
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"374\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cbb 
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"373\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cbb 
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"25\"," +"\"Period\":\""+periodNumber+"\"" +"}"; nba
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"51\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // wnba
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"1\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // mlb
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"18\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // japan baseball
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"21\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // college baseball
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"70\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // nfl
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"111\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cfl
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"184\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // horse
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"97\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // arena
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"339\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // nhl
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"98\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cf
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"99\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cf2
						jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"378\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // cfx and fcs
				
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"132\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"140\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"133\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"150\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"573\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"147\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"148\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					jsonleaguedat = jsonleaguedat+",{\"IdSport\":\"130\"," +"\"Period\":\""+periodNumber+"\"" +"}"; // golf
					
					jsonleaguedat = jsonleaguedat+"]";
										System.out.println("actually posting to "+LOGON_SITE+"..."+form_data.toString());
										System.out.println("SPORT="+sport);
										rsp = PostUncJson(LOGON_SITE,jsonleaguedat);
										System.out.println("END OF POSITING JSON SCHEDULE TO UNC!!!!!\n\n\n\n");
										
										
//get lines page end										
// this code below parses lines page
						System.out.println("amount is "+amount);
						String sprd = "";
						String ju = "";
						System.out.println("gamenum is "+gamenum);
						System.out.println("gamenum2 is "+gamenum2);
						
							   
							  
					
	
			
			System.out.println("sport is "+sport);
			
			String realgame = getGame(gamenum+"",sport);
			System.out.println("real game is "+realgame);
			int realgamenum = gamenum;
			int realgamenum2 = gamenum2;
			boolean uncfakegamenum = false;
			String rodat = dat;
			
			if(!realgame.equals(""))
			{
				while(rodat.indexOf("RotationNumber") != -1)
				{
					rodat = rodat.substring(rodat.indexOf("RotationNumber\":")+16);
					String rotnum = rodat.substring(0,rodat.indexOf(","));
					rodat = rodat.substring(rodat.indexOf("Name\":\"")+7);
					String rotteam1 = rodat.substring(0,rodat.indexOf("\""));
					rodat = rodat.substring(rodat.indexOf("Name\":\"")+7);
					String rotteam2 = rodat.substring(0,rodat.indexOf("\""));
					String[] myArray2 = new String[2]; 
					myArray2 = Hash.getTeams(rotteam1,rotteam2);
					rotteam1 = myArray2[0];
					rotteam2 = myArray2[1];
					String thisgame = rotteam1+"@"+rotteam2;
					
					if(thisgame.equals(realgame))
					{
						if(!rotnum.equals(gamenum+""))
						{
							uncfakegamenum = true;
							System.out.println("\n\nUNCLE FAKE GAME NUM!!!!!");
							int rotnumI = new Integer(rotnum).intValue();
							int gamenumI = new Integer(gamenum).intValue();
							if(Math.abs(rotnumI-gamenumI) > 200)
							{
								System.out.println("game numbers off too much continuing to search....");
								continue;
							}
							System.out.print("changing from realgamenum of "+gamenum+" to fake game num of ");
							if(gamenumI % 2 ==0) // even
							{
								rotnumI++;
								gamenum = rotnumI;
								gamenum2 = gamenum;
							}
							else
							{
								gamenum = rotnumI;
								gamenum2 = gamenum+1;
							}
							
							
							System.out.println(rotnumI);
							System.out.println("game num1 ="+gamenum);
							System.out.println("game num2 ="+gamenum2);
						}
						else
						{
							System.out.println("uncle real game num!!");
						}
						break;
					}
					else
					{
						continue;
					}
					
				}
			}
	
			
			if(dat.indexOf("RotationNumber\":"+gamenum+",") == -1)
			{
				System.out.println(gamenum+" Line not hung!!!");
				return;
				
			}

			try
			{
				String limitlookup = dat.substring(0,dat.indexOf("RotationNumber\":"+gamenum+","));
				limitlookup = limitlookup.substring(limitlookup.lastIndexOf("Limits"));
				
				if(type.equals(""))
				{
					limitlookup = limitlookup.substring(limitlookup.indexOf("\"Spread\":")+9);
					limitlookup = limitlookup.substring(0,limitlookup.indexOf(","));
				}
				else if(type.equals("money"))
				{
					limitlookup = limitlookup.substring(limitlookup.indexOf("\"MoneyLine\":")+12);
					limitlookup = limitlookup.substring(0,limitlookup.indexOf(","));
					
				}
				else // total
				{
					limitlookup = limitlookup.substring(limitlookup.indexOf("\"Total\":")+8);
					limitlookup = limitlookup.substring(0,limitlookup.indexOf(","));
					
				}
					
					int amti = new Integer(amount).intValue();
					int limi = new Integer(limitlookup).intValue();
					if(amti < limi)
					{
						amount = ""+amti;
					}
					else
					{
						amount = ""+limi;
					}
					
					System.out.println("new amount for limitlookup is "+amount);
				
			}
			catch(Exception ex)
			{
				System.out.println("exception limitlookup "+ex);
				
			}
			
			gmgeneral = dat.substring(dat.indexOf("RotationNumber\":"+gamenum+",")+19);
			
				gmgeneral = gmgeneral.substring(0,gmgeneral.indexOf("[]}"));
			 


			//System.out.println("gmgeneral "+gmgeneral);
/*
			try
			{
				Thread.sleep(5000);
			}
			catch(Exception ex)
			{
				
			}
*/

			team1 = gmgeneral.substring(gmgeneral.indexOf("Name\":\"")+7);
			team1 = team1.substring(0,team1.indexOf("\""));



			System.out.println("gmgeneral "+gmgeneral);
			System.out.println("team1 "+team1);



			String gmsprd1 = gmgeneral.substring(gmgeneral.indexOf("\"Spread\""));
			gmsprd1 = gmsprd1.substring(0,gmsprd1.indexOf("]"));
			while(true) // i have to go through all buypoints 0.0 and find juice that is <= 130
			{
				if(gmsprd1.indexOf("\"BuyPoints\":0.0") == -1) // no more buypoints 0 so break;
				{
					break;
				}
					
				gmid1 = gmsprd1.substring(0,gmsprd1.indexOf("\"BuyPoints\":0.0"));
				gmid1 = gmid1.substring(gmid1.lastIndexOf("Id\":\"")+5);
				lineSearch1 = gmid1;
				gmid1 = gmid1.substring(0,gmid1.indexOf("\""));
				lineSearch1 = lineSearch1.substring(lineSearch1.indexOf("Points\":")+8); 
				sprd = lineSearch1.substring(0,lineSearch1.indexOf(","));
				lineSearch1 = lineSearch1.substring(lineSearch1.indexOf("Odds\":")+6); 
				ju = lineSearch1.substring(0,lineSearch1.indexOf(","));
				lineSearch1 = sprd+" "+ju;
				int juint = new Integer(ju).intValue();
				if(Math.abs(juint) >= 100 && Math.abs(juint) <= 130) // found a good juice so break
				{
					break;
				}
				else
				{
					gmsprd1 = gmsprd1.substring(gmsprd1.indexOf(gmid1));
					gmsprd1 = gmsprd1.substring(gmsprd1.indexOf("}"));
					
				}
			}

			// new code to change lineSearch1 option list
			

			System.out.println("gmid1 "+gmid1);
			System.out.println("lineSearch1 "+lineSearch1);


			try
			{
				//"MoneyLine":[{"Id":"2_219412434_0_115_0_0_0","Points":0.0,"Odds":115,"BuyPoints":0.0}]
				String gmmoney1 = gmgeneral.substring(gmgeneral.indexOf("\"MoneyLine\""));
				gmmoney1 = gmmoney1.substring(0,gmmoney1.indexOf("]"));
				gmidmoney1 = gmmoney1.substring(0,gmmoney1.indexOf("\"BuyPoints\":0.0"));
				gmidmoney1 = gmidmoney1.substring(gmidmoney1.lastIndexOf("Id\":\"")+5);
				lineSearchmoney1 = gmidmoney1;
				gmidmoney1 = gmidmoney1.substring(0,gmidmoney1.indexOf("\""));
		
				lineSearchmoney1 = lineSearchmoney1.substring(lineSearchmoney1.indexOf("Odds\":")+6); 
				ju = lineSearchmoney1.substring(0,lineSearchmoney1.indexOf(","));
				lineSearchmoney1 = ju;
				System.out.println("gmidmoney1 "+gmidmoney1);
				System.out.println("lineSearchmoney1 "+lineSearchmoney1);



			  }
			catch(Exception ex)
			  {
				System.out.println("exception on getting ml1 "+ex);

			  }
	
			try
			{
				String gmover = gmgeneral.substring(gmgeneral.indexOf("\"Total\""));
				gmover = gmover.substring(0,gmover.indexOf("]"));
				while(true) // i have to go through all buypoints 0.0 and find juice that is <= 130
				{
					if(gmover.indexOf("\"BuyPoints\":0.0") == -1) // no more buypoints 0 so break;
					{
						break;
					}
				
				
					gmidover = gmover.substring(0,gmover.indexOf("\"BuyPoints\":0.0"));
					gmidover = gmidover.substring(gmidover.lastIndexOf("Id\":\"")+5);
					lineSearchover = gmidover;
					gmidover = gmidover.substring(0,gmidover.indexOf("\""));
					lineSearchover = lineSearchover.substring(lineSearchover.indexOf("Points\":")+8); 
					sprd = lineSearchover.substring(0,lineSearchover.indexOf(","));
					lineSearchover = lineSearchover.substring(lineSearchover.indexOf("Odds\":")+6); 
					ju = lineSearchover.substring(0,lineSearchover.indexOf(","));
					lineSearchover = sprd+" "+ju;
					System.out.println("gmidover "+gmidover);
					System.out.println("lineSearchover "+lineSearchover);
					int juint = new Integer(ju).intValue();
					if(Math.abs(juint) >= 100 && Math.abs(juint) <= 130) // found a good juice so break
					{
						break;
					}
					else
					{
						gmover = gmover.substring(gmover.indexOf(gmidover));
						gmover = gmover.substring(gmover.indexOf("}"));
						
					}
				}


			  }
			catch(Exception ex)
			  {
				System.out.println("exception on getting over "+ex);

			  }






			
		
			gmgeneral2 = dat.substring(dat.indexOf("RotationNumber\":"+gamenum2+",")+19);
			gmgeneral2 = gmgeneral2.substring(0,gmgeneral2.indexOf("[]}"));



			team2 = gmgeneral2.substring(gmgeneral2.indexOf("Name\":\"")+7);
			team2 = team2.substring(0,team2.indexOf("\""));



			System.out.println("gmgeneral2 "+gmgeneral2);
			System.out.println("team2 "+team2);



			String gmsprd2 = gmgeneral2.substring(gmgeneral2.indexOf("\"Spread\""));
			gmsprd2 = gmsprd2.substring(0,gmsprd2.indexOf("]"));
			while(true) // i have to go through all buypoints 0.0 and find juice that is <= 130
			{
				if(gmsprd2.indexOf("\"BuyPoints\":0.0") == -1) // no more buypoints 0 so break;
				{
					break;
				}
				gmid2 = gmsprd2.substring(0,gmsprd2.indexOf("\"BuyPoints\":0.0"));
				gmid2 = gmid2.substring(gmid2.lastIndexOf("Id\":\"")+5);
				lineSearch2 = gmid2;
				gmid2 = gmid2.substring(0,gmid2.indexOf("\""));
				lineSearch2 = lineSearch2.substring(lineSearch2.indexOf("Points\":")+8); 
				sprd = lineSearch2.substring(0,lineSearch2.indexOf(","));
				lineSearch2 = lineSearch2.substring(lineSearch2.indexOf("Odds\":")+6); 
				ju = lineSearch2.substring(0,lineSearch2.indexOf(","));
				lineSearch2 = sprd+" "+ju;
				int juint = new Integer(ju).intValue();
				if(Math.abs(juint) >= 100 && Math.abs(juint) <= 130) // found a good juice so break
				{
					break;
				}
				else
				{
					gmsprd2 = gmsprd2.substring(gmsprd2.indexOf(gmid2));
					gmsprd2 = gmsprd2.substring(gmsprd2.indexOf("}"));
					
				}
			}

			// new code to change lineSearch1 option list
			

			System.out.println("gmid2 "+gmid2);


			try
			{
				String gmmoney2 = gmgeneral2.substring(gmgeneral2.indexOf("\"MoneyLine\""));
				gmmoney2 = gmmoney2.substring(0,gmmoney2.indexOf("]"));
				gmidmoney2 = gmmoney2.substring(0,gmmoney2.indexOf("\"BuyPoints\":0.0"));
				gmidmoney2 = gmidmoney2.substring(gmidmoney2.lastIndexOf("Id\":\"")+5);
				lineSearchmoney2 = gmidmoney2;
				gmidmoney2 = gmidmoney2.substring(0,gmidmoney2.indexOf("\""));
		
				lineSearchmoney2 = lineSearchmoney2.substring(lineSearchmoney2.indexOf("Odds\":")+6); 
				ju = lineSearchmoney2.substring(0,lineSearchmoney2.indexOf(","));
				lineSearchmoney2 = ju;
			



			  }
			catch(Exception ex)
			  {
				System.out.println("exception on getting ml2 "+ex);

			  }
	
			try
			{
			String gmunder = gmgeneral2.substring(gmgeneral2.indexOf("\"Total\""));
			gmunder = gmunder.substring(0,gmunder.indexOf("]"));
			while(true) // i have to go through all buypoints 0.0 and find juice that is <= 130
			{
				if(gmunder.indexOf("\"BuyPoints\":0.0") == -1) // no more buypoints 0 so break;
				{
					break;
				}
			
			
				gmidunder = gmunder.substring(0,gmunder.indexOf("\"BuyPoints\":0.0"));
				gmidunder = gmidunder.substring(gmidunder.lastIndexOf("Id\":\"")+5);
				lineSearchunder = gmidunder;
				gmidunder = gmidunder.substring(0,gmidunder.indexOf("\""));
				lineSearchunder = lineSearchunder.substring(lineSearchunder.indexOf("Points\":")+8); 
				sprd = lineSearchunder.substring(0,lineSearchunder.indexOf(","));
				lineSearchunder = lineSearchunder.substring(lineSearchunder.indexOf("Odds\":")+6); 
				ju = lineSearchunder.substring(0,lineSearchunder.indexOf(","));
				lineSearchunder = sprd+" "+ju;
				int juint = new Integer(ju).intValue();
				if(Math.abs(juint) >= 100 && Math.abs(juint) <= 130) // found a good juice so break
				{
					break;
				}
				else
				{
					gmunder = gmunder.substring(gmunder.indexOf(gmidunder));
					gmunder = gmunder.substring(gmunder.indexOf("}"));
					
				}
			}


			  }
			catch(Exception ex)
			  {
				System.out.println("exception on getting underer "+ex);

			  }
				if(uncfakegamenum)
				{
					gamenum = realgamenum;
					gamenum2 = realgamenum2;
				}

				}

// end parsing lines page	
//beginning of submitting bet
else if(site.indexOf("unc") != -1 || site.indexOf("amc") != -1 || site.indexOf("unb") != -1  || site.indexOf("una") != -1)
{
			
			// see if amount is good
			
			
			
			String pitcherbool = "false";
			if(sport.equalsIgnoreCase("MLB"))
			{
				pitcherbool = "true";
			}
			String ticketnumber = "";
			String rsp4 = "";
			String rsp3 = "";
			String rsp2 = "";
			System.out.println("confirming for uncle...");
			String jsonbet = "";
			jsonbet = "[{\"BetType\": \"S\",\"TotalPicks\": 1,\"IdTeaser\": 0,\"IsFreePlay\": false,\"Amount\": \""+amount+"\",\"RoundRobinOptions\": [],\"Wagers\":";
			jsonbet = jsonbet+"[{\"Id\": \""+gmid+"\",\"PitcherVisitor\": "+pitcherbool+",\"PitcherHome\": "+pitcherbool+"}],\"AmountCalculation\": \"A\",\"ContinueOnPush\": true}]";
			
			while(true)
			{
				//rsp2 = PostUncJson("https://api.cog.cr/api/wager/AddBet/",jsonbet);
				rsp2 = PostUncJson("https://"+host+"/player-api/api/wager/AddBet/",jsonbet);	//csr20190713
				long nowlong = new java.util.Date().getTime();
				//writeToFile("d:\\sb\\asiuncpuzzle"+site+nowlong,rsp2);
				
				String amtsearch = "Your maximum wager is ";
				if(rsp2.indexOf(amtsearch) != -1)
				{
					
					amount = rsp2.substring(rsp2.indexOf(amtsearch)+amtsearch.length());
					amount = amount.substring(0,amount.indexOf(" "));
					System.out.println("NEW REDUCED AMOUNT IS "+amount);
					
				}
				
				
				// check for captcha
				String captcha = "";
				String jsonbet2 = "";
				//if(rsp2.indexOf("CaptchaImage\":null") == -1) // theres a puzzle!!!
									    
				String captchasearch = "\"CaptchaImage\":\"data:image/png;base64,";
				//if(rsp2.indexOf("CaptchaImage\":null") != -1) // theres a puzzle!!!
				if(rsp2.indexOf(captchasearch) != -1) // theres a puzzle!!!
				
				{
					System.out.println("OH NO!!! theres a captcha!!!!");
					String captchastring = rsp2.substring(rsp2.indexOf(captchasearch)+captchasearch.length());
					captchastring = captchastring.substring(0,captchastring.indexOf("\""));
					//System.out.println("captchastring = "+captchastring);
					captcha = solvepuzzle(captchastring,nowlong);
					
				}
				else
				{
					System.out.println("NO CAPTCHA ----  YES!!!");
				}
				
				jsonbet2 = "{\"Details\": [{\"BetType\": \"S\",\"TotalPicks\": 1,\"IdTeaser\": 0,\"IsFreePlay\": false,\"Amount\": \""+amount+"\",\"RoundRobinOptions\": [],\"Wagers\":";
				jsonbet2 = jsonbet2 +"[{\"Id\": \""+gmid+"\",\"PitcherVisitor\": "+pitcherbool+",\"PitcherHome\": "+pitcherbool+"}],\"AmountCalculation\": \"A\",\"ContinueOnPush\": true}],\"DelayUntilSignature\": \"\",\"DelayUntilUTC\": \"\",\"CaptchaMessage\": \""+captcha+"\"}";		
				//rsp3 = PostUncJson("https://api.cog.cr/api/wager/SaveBet/",jsonbet2);
				rsp3 = PostUncJson("https://"+host+"/player-api/api/wager/SaveBet/",jsonbet2);	//csr20190713
				if(rsp3.indexOf("\"Captcha Validation Fail") != -1) // failed in solving lets try again!
				{
					System.out.println("captcha fail trying again");
					refundrequest(nowlong);
					
					continue;
				}
				else // solved it!
				{
					System.out.println("solved it "+captcha);
					break;
				}
			}
			
			
			try
			{
			ticketnumber = rsp3.substring(rsp3.indexOf("TicketNumber\":")+14);
			ticketnumber = ticketnumber.substring(0,ticketnumber.indexOf(","));
			ticketnumber = ticketnumber.trim();
			String jsonbet3 = "{\"TicketNumber\": "+ticketnumber+"}";
			//rsp4 = PostUncJson("https://api.cog.cr/api/wager/confirmBet/",jsonbet3);
			rsp4 = PostUncJson("https://"+host+"/player-api/api/wager/confirmBet/",jsonbet3);	//csr20190713
			}
			catch(Exception ex)
			{
				System.out.println("exception getting ticket number shit happened "+ex);
				
			}
			
		rsp = rsp2+rsp3+rsp4;
}									
//submit bet end										
										
				

// method to postjson to unc website
  public String PostUncJson(String LOGON_SITE,String jsoninp)
{




	  try
	  {
	 
		URL api = new URL(LOGON_SITE);
				
		
		HttpURLConnection apiConnection = (HttpURLConnection)api.openConnection();
		apiConnection.setRequestMethod("POST");
		apiConnection.setDoOutput(true);
		apiConnection.setDoInput(true);
		
		apiConnection.addRequestProperty("User-Agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
		//apiConnection.addRequestProperty("Cookie","__nxquid=gVTZOAAAAACghYUiR6wZpA==81370014;__nxqsid=15632274000014");
								Cookie[] cookies2 = client.getState().getCookies();
								
								for (int i = 0; i < cookies2.length; i++) 
								{
									Cookie cookie = cookies2[i];
									if(!cookString.equals(""))
									{
										cookString = cookString+";";
									}	
										cookString = cookString+cookie.getName()+"="+cookie.getValue();
								}
								apiConnection.addRequestProperty("Cookie",cookString);							
		apiConnection.addRequestProperty("Content-Type", "application/json; charset=utf-8");
		apiConnection.addRequestProperty("Accept", "application/json; charset=utf-8");		
		apiConnection.addRequestProperty("Authorization", "Bearer "+accesstoken);
		System.out.println("BBB3a");
		OutputStream os = apiConnection.getOutputStream();
		os.write(jsoninp.getBytes("UTF-8"));
		os.close();
		
		System.out.println("CCC");
		String jsondat = getStreamContent(apiConnection.getInputStream());
		return jsondat;
		
	  }
	  catch(Exception ex)
	  {
		  System.out.println("ex"+ex);
		  unccount++;
		  if(unccount >2)
		  {
			  return null;
		  }
		  else
		  {
			return PostUncJson(LOGON_SITE,jsoninp);
		  }	
	  }
								 
								  		
							
 }	

// method to postunc no json
public String PostUnc(String LOGON_SITE,NameValuePair[] form_data,String referer)
{
	
	try
	{
		
 authpost = new PostMethod(LOGON_SITE);
 authpost.setRequestBody( form_data);
 if(!referer.equals(""))
 {
	authpost.addRequestHeader("Referer",referer);
 }	
		authpost.addRequestHeader("Cache-Control", "max-age=0");
		authpost.addRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		authpost.addRequestHeader("User-Agent", "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.106 Safari/535.2");
		authpost.addRequestHeader("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8");
		authpost.addRequestHeader("Accept-Language", "en-US,en;q=0.8");
		authpost.addRequestHeader("Accept-Charset", "ISO-8859-1,utf-8;q=0.7,*;q=0.3");
		//authpost.addRequestHeader("Host","api.cog.cr"); 
		authpost.addRequestHeader("Host",host);	//csr20190713
		statusCode = client.executeMethod(authpost);
		responseBody = authpost.getResponseBody();
		 
		
		 
		String dat = new String(responseBody);
		writeToFile("d:\\sb\\asi2-"+postcounter+site+".html",new String(responseBody));
		
		postcounter++;
	
											String loca=authpost.getResponseHeader("Location").getValue();
											String token= loca.substring(loca.indexOf("t=")+2);
											
											String dat2 = Get(loca);
											writeToFile("d:\\sb\\asi2dat2unc-"+postcounter+site+".html",dat2);
											/*
											GetMethod gg = new GetMethod(loca);
											System.out.println("redirect 1="+loca);
											client.executeMethod(gg);
											while(gg.getResponseHeader("Location") != null)
											{
												System.out.println("redirect loop");
												String loca2=gg.getResponseHeader("Location").getValue();
												gg = new GetMethod(loca2);
												client.executeMethod(gg);
											}
											*/	
											System.out.println("we are done redirecting!!!");
											System.out.println("host is "+host);
											System.out.println("token is "+token);
											System.out.println("now we will post to identity/customerLoginFromToken/ token and version 1.3.31");
											
											String jsoninp = 
							"{\"token\":\""+token+"\"," +
							"\"version\":\"1.3.31\"" +
							"}";
							//ATTENTION THIS VERSION KEEPS CHANGING!!!! KEEP AN EYE ON IT!!!!
								  try
								  {
								 
									//URL api = new URL("https://api.cog.cr/identity/customerLoginFromToken");
									URL api = new URL("https://"+host+"/player-api/identity/customerLoginFromToken");	//csr20190713
											
									
									HttpURLConnection apiConnection = (HttpURLConnection)api.openConnection();
									apiConnection.setRequestMethod("POST");
									apiConnection.setDoOutput(true);
									apiConnection.setDoInput(true);
									//apiConnection.addRequestProperty("Cookie","__nxquid=gVTZOAAAAACghYUiR6wZpA==81370014;__nxqsid=15632274000014");
									Cookie[] cookies2 = client.getState().getCookies();
								
								for (int i = 0; i < cookies2.length; i++) 
								{
									Cookie cookie = cookies2[i];
									if(!cookString.equals(""))
									{
										cookString = cookString+";";
									}	
										cookString = cookString+cookie.getName()+"="+cookie.getValue();
								}
								apiConnection.addRequestProperty("Cookie",cookString);									
									apiConnection.addRequestProperty("User-Agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

							apiConnection.addRequestProperty("Content-Type", "application/json; charset=utf-8");
							apiConnection.addRequestProperty("Accept", "application/json; charset=utf-8");		
									System.out.println("BBB3b");
									OutputStream os = apiConnection.getOutputStream();
									os.write(jsoninp.getBytes("UTF-8"));
									os.close();
									
									System.out.println("CCC");
									String jsondat = getStreamContent(apiConnection.getInputStream());
									System.out.println("response="+jsondat);
									accesstoken = jsondat.substring(jsondat.indexOf("AccessToken\":\"")+14);
									accesstoken = accesstoken.substring(0,accesstoken.indexOf("\""));
									System.out.println("accesstoken="+accesstoken);
								  }
								  catch(Exception ex)
								  {
									  System.out.println("ex"+ex);
									  return null;
								  }
						
								  try
								  {
								 
									//URL api = new URL("https://api.cog.cr/api/customer/balance");
									URL api = new URL("https://"+host+"/player-api/api/customer/account_balance");	//csr20190713
									//URL api = new URL("https://api.cog.cr/api/wager/sportsavailablebyplayeronleague/false");
									//URL api = new URL("https://api.cog.cr/api/customer/availableoptions");		
									 
									byte[] bytes = accesstoken.getBytes("UTF-8");
									System.out.println("AAA");
									String encoded = DatatypeConverter.printBase64Binary(bytes); //493 cb
       
        ;
									HttpURLConnection apiConnection = (HttpURLConnection)api.openConnection();
									apiConnection.setRequestMethod("GET");
									apiConnection.setDoOutput(true);
									apiConnection.setDoInput(true);
									
									apiConnection.addRequestProperty("User-Agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

							System.out.println("host="+host);
							apiConnection.addRequestProperty("Accept", "application/json, text/plain, */*");	
							apiConnection.addRequestProperty("Accept-Language", "en-US,en;q=0.9");	
							//apiConnection.addRequestProperty("Host", "api.cog.cr");	
							apiConnection.addRequestProperty("Host", host);	//csr20190713
							apiConnection.addRequestProperty("Connection", "keep-alive");	
							apiConnection.addRequestProperty("Origin", "http://"+host);
							apiConnection.addRequestProperty("Referer", "http://"+host+"/cog/index.html");
							//apiConnection.addRequestProperty("Authorization", "Bearer "+encoded);			
							apiConnection.addRequestProperty("Authorization", "Bearer "+accesstoken);		
//apiConnection.addRequestProperty("Cookie","__nxquid=gVTZOAAAAACghYUiR6wZpA==81370014;__nxqsid=15632274000014");							
								Cookie[] cookies2 = client.getState().getCookies();
								
								for (int i = 0; i < cookies2.length; i++) 
								{
									Cookie cookie = cookies2[i];
									if(!cookString.equals(""))
									{
										cookString = cookString+";";
									}	
										cookString = cookString+cookie.getName()+"="+cookie.getValue();
								}
								apiConnection.addRequestProperty("Cookie",cookString);							
									System.out.println("BBB3c");
									/*
									OutputStream os = apiConnection.getOutputStream();
									String jsoninp2 = "";
									os.write(jsoninp2.getBytes("UTF-8"));
									os.close();
									*/
									System.out.println("CCC");
									String jsondat = getStreamContent(apiConnection.getInputStream());
									System.out.println("response2="+jsondat);
									writeToFile("d:\\sb\\enterasibeg"+site+"1.html",jsondat);
									
								  }
								  catch(Exception ex)
								  {
									  System.out.println("ex"+ex);
									  String excepstr = ex.toString();
									  if(excepstr.indexOf("498") != -1)
									  {
										  System.out.println("UNC VERSION ERROR UNCLE CALL CROD OR O!!!!!!");
										  if(this.getComputerName().equals("SOCSERV2"))
											{
												notifyStage("UNC VERSION ERROR UNCLE CALL CROD OR O!!!!!!");	
											}
										  
									  }
									 // System.exit(0);
									  return null;
								  }		
								   
						
										
		
				
			     		 
				 return dat;
				
		
		
		
		}
		catch(Exception ex)
	    {
		System.out.println("EXCEPTIONzzz "+ex);
			gli.close();
			System.exit(0);
	    }
		return "";
 }

 