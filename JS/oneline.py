with open("../fastas/reference.fasta","r") as ref:
        refseq = ""
        for line in ref: 
                    
            if line.startswith(">"):
                pass
            else:
                refseq += line[0:-1]
print(refseq)