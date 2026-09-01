import os
import pandas as pd

# testfile = "/projectsc/f_grigorie_1/sarscov2i_samtools/illumina_hiseq_2500/ERR6882003-bbmap-var.vcf"
testfile = "ERR6882003-bbmap-var.vcf"
testfoldername = "illumina_hiseq_2500"

directories = ["DE_miseq-bwa-pad","illumina_hiseq_2500-bwa-pad","illumina_miseq-bwa-pad","nextseq_500-bwa-pad","nextseq_550-bwa-pad","CRA004571-bwa-pad"]
instrumentname = ['illumina_miseq',"illumina_hiseq_2500",'illumina_miseq', "nextseq_500","nextseq_550","Chinese_short_read_platform"]

outputdf = pd.DataFrame(columns=["coordinate",
								"reference",
								"alternate",
								'instrument'])

directorybase = "./"
# insertstatement= "INSERT INTO `mutationsfinalfinal` (`coordinate`, `reference`, `alternate`, `instrument`, `mutcount`) VALUES"
# sqloutput = ""

# sqloutput+=insertstatement
for i in range(len(directories)):
	print(directories[i])
	tmpdir = directorybase+directories[i]+"/"
	for file in os.listdir(tmpdir):
		# print(file)

		if file.endswith(".SNV.vcf"):
			print(file)
			try:

				with open(tmpdir+file,"r") as vcf:
					
					for line in vcf:
						fileli = []
						if line.startswith("#"):
							pass

						else:
							sline = line.split("\t")
							fileli.append(int(sline[1])-6000)
							fileli.append(sline[3].strip())
							fileli.append(sline[4].strip())
							fileli.append(instrumentname[i])
							# sqloutput+="\n({coord},{ref},{alt},{instru}"


							outputdf.loc[len(outputdf.index)] = fileli

					break
			except:
				print(file)
				print("ERROR~~~~~~~~~~~~~~~~~~~~~~~~~~~~~")

				
outputdf.to_csv("outputtest.csv")