;Universal PT2 and PT3 player for ZX Spectrum and MSX
;(c)2004-2007 S.V.Bulba <vorobey@mail.khstu.ru>
;http://bulba.untergrund.net (http://bulba.at.kz)
;(c)2014 modded for speed by introspec (15% faster)

;Conditional assembly
;2) Current position counter at (START+11)
CurPosCounter=1
;4) Allow loop checking and disabling
LoopChecker=0

;Features
;--------
;-Can be compiled at any address (i.e. no need rounding ORG
; address).
;-Variables (VARS) can be located at any address (not only after
;code block).
;-INIT subprogram checks PT3-module version and rightly
; generates both note and volume tables outside of code block
; (in VARS).
;-Two portamento (spc. command 3xxx) algorithms (depending of
; PT3 module version).
;-New 1.XX and 2.XX special command behaviour (only for PT v3.7
; and higher).
;-Any Tempo value are accepted (including Tempo=1 and Tempo=2).
;-Fully compatible with Ay_Emul PT3 and PT2 players codes.
;-See also notes at the end of this source code.

;Limitations
;-----------
;-Can run in RAM only (self-modified code is used).
;-PT2 position list must be end by #FF marker only.

;Warning!!! PLAY subprogram can crash if no module are loaded
;into RAM or INIT subprogram was not called before.

;Call MUTE or INIT one more time to mute sound after stopping
;playing 

TonA	EQU 0
TonB	EQU 2
TonC	EQU 4
Noise	EQU 6
Mixer	EQU 7
AmplA	EQU 8
AmplB	EQU 9
AmplC	EQU 10
Env	EQU 11
EnvTp	EQU 13

;Entry and other points
;START initialize playing of module at MDLADDR
;START+3 initialization with module address in HL
;START+5 play one quark
;START+8 mute
;START+10 setup and status flags
;START+11 current position value (byte) (optional)

START
	LD HL,MDLADDR
	JR INIT
	JP PLAY
	;JP ROUT
	JR MUTE
SETUP	DB 0 ;set bit0, if you want to play without looping
	     ;(optional);
	     ;set bit1 for PT2 and reset for PT3 before
	     ;calling INIT;
	     ;bits2-3: %00-ABC, %01 ACB, %10 BAC (optional);
	     ;bits4-6 are not used
	     ;bit7 is set each time, when loop point is passed
	     ;(optional)
	IF CurPosCounter
CurPos	DB 0 ;for visualization only (i.e. no need for playing)
	ENDIF

	IF LoopChecker
CHECKLP	LD HL,SETUP
	SET 7,(HL)
	BIT 0,(HL)
	RET Z
	POP HL
	LD HL,DelyCnt
	INC (HL)
	LD HL,ChanA+CHP.NtSkCn
	INC (HL)
	ENDIF

MUTE	XOR A
	LD H,A
	LD L,A
	LD (AYREGS+AmplA),A
	LD (AYREGS+AmplB),HL
	JP ROUT

INIT
;HL - AddressOfModule
	LD A,(START+10)
	AND 2
	JR NZ,INITPT2

	CALL SETMDAD
	PUSH HL
	LD DE,100
	ADD HL,DE
	LD A,(HL)
	LD (Delay),A
	PUSH HL
	POP IX
	ADD HL,DE
	LD (CrPsPtr),HL
	LD E,(IX+102-100)
	INC HL

	IF CurPosCounter
	LD A,L
	LD (PosSub+1),A
	ENDIF

	ADD HL,DE
	LD (LPosPtr),HL
	POP DE
	LD L,(IX+103-100)
	LD H,(IX+104-100)
	ADD HL,DE
	LD (PatsPtr),HL
	LD HL,169
	ADD HL,DE
	LD (OrnPtrs),HL
	LD HL,105
	ADD HL,DE
	LD (SamPtrs),HL
	LD A,(IX+13-100) ;EXTRACT VERSION NUMBER
	SUB #30
	JR C,L20
	CP 10
	JR C,L21
L20	LD A,6
L21	LD (Version),A
	PUSH AF ;VolTable version
	CP 4
	LD A,(IX+99-100) ;TONE TABLE NUMBER
	RLA
	AND 7
	PUSH AF ;NoteTable number
	LD HL,(e_-SamCnv-2)*256+#18
	LD (SamCnv),HL
	LD A,#BA
	LD (OrnCP),A
	LD (SamCP),A
	LD A,#7B
	LD (OrnLD),A
	LD (SamLD),A
	LD A,#87
	LD (SamClc2),A
	LD BC,PT3PD
	LD HL,0
	LD DE,PT3EMPTYORN
	JR INITCOMMON

INITPT2	LD A,(HL)
	LD (Delay),A
	PUSH HL
	PUSH HL
	PUSH HL
	INC HL
	INC HL
	LD A,(HL)
	INC HL
	LD (SamPtrs),HL
	LD E,(HL)
	INC HL
	LD D,(HL)
	POP HL
	AND A
	SBC HL,DE
	CALL SETMDAD
	POP HL
	LD DE,67
	ADD HL,DE
	LD (OrnPtrs),HL
	LD E,32
	ADD HL,DE
	LD C,(HL)
	INC HL
	LD B,(HL)
	LD E,30
	ADD HL,DE
	LD (CrPsPtr),HL
	LD E,A
	INC HL

	IF CurPosCounter
	LD A,L
	LD (PosSub+1),A
	ENDIF

	ADD HL,DE
	LD (LPosPtr),HL
	POP HL
	ADD HL,BC
	LD (PatsPtr),HL
	LD A,5
	LD (Version),A
	PUSH AF
	LD A,2
	PUSH AF
	LD HL,#51CB
	LD (SamCnv),HL
	LD A,#BB
	LD (OrnCP),A
	LD (SamCP),A
	LD A,#7A
	LD (OrnLD),A
	LD (SamLD),A
	LD A,#80
	LD (SamClc2),A
	LD BC,PT2PD
	LD HL,#8687
	LD DE,PT2EMPTYORN

INITCOMMON

	;LD (PTDECOD+1),BC
	LD (PDCALL1+1),BC
	LD (PDCALL2+1),BC
	LD (PDCALL3+1),BC
	LD (PsCalc),HL
	PUSH DE

;note table data depacker
;(c) Ivan Roshin
	LD DE,T_PACK
	LD BC,T1_+(2*49)-1
TP_0	LD A,(DE)
	INC DE
	CP 15*2
	JR NC,TP_1
	LD H,A
	LD A,(DE)
	LD L,A
	INC DE
	JR TP_2
TP_1	PUSH DE
	LD D,0
	LD E,A
	ADD HL,DE
	ADD HL,DE
	POP DE
TP_2	LD A,H
	LD (BC),A
	DEC BC
	LD A,L
	LD (BC),A
	DEC BC
	SUB low (#F8*2)
	JR NZ,TP_0

	IF LoopChecker
	LD HL,SETUP
	RES 7,(HL)

	IF CurPosCounter
	INC HL
	LD (HL),A
	ENDIF

	ELSE

	IF CurPosCounter
	LD (CurPos),A
	ENDIF
	
	ENDIF

	LD HL,VARS
	LD (HL),A
	LD DE,VARS+1
	LD BC,VAR0END-VARS-1
	LDIR
	LD (AdInPtA),HL ;ptr to zero
	INC A
	LD (DelyCnt),A
	LD HL,#F001 ;H - CHP.Volume, L - CHP.NtSkCn
	LD (ChanA+CHP.NtSkCn),HL
	LD (ChanB+CHP.NtSkCn),HL
	LD (ChanC+CHP.NtSkCn),HL
	POP HL
	LD (ChanA+CHP.OrnPtr),HL
	LD (ChanB+CHP.OrnPtr),HL
	LD (ChanC+CHP.OrnPtr),HL

	POP AF

;NoteTableCreator (c) Ivan Roshin
;A - NoteTableNumber*2+VersionForNoteTable
;(xx1b - 3.xx..3.4r, xx0b - 3.4x..3.6x..VTII1.0)

	LD HL,NT_DATA
	PUSH DE
	LD D,B
	ADD A,A
	LD E,A
	ADD HL,DE
	LD E,(HL)
	INC HL
	SRL E
	SBC A,A
	AND #A7 ;#00 (NOP) or #A7 (AND A)
	LD (L3),A
	EX DE,HL
	POP BC ;BC=T1_
	ADD HL,BC

	LD A,(DE)
	ADD A,low T_
	LD C,A
	ADC A,high T_;T_/256
	SUB C
	LD B,A
	PUSH BC
	LD DE,NT_
	PUSH DE

	LD IXL,12
L1	LD C,(HL)
	INC HL
	PUSH HL
	LD B,(HL)

	PUSH DE
	EX DE,HL
	LD DE,23
	LD IXH,8

L2	SRL B
	RR C
L3	DB #19	;AND A or NOP
	LD A,C
	ADC A,D	;=ADC 0
	LD (HL),A
	INC HL
	LD A,B
	ADC A,D
	LD (HL),A
	ADD HL,DE
	DEC IXH
	JR NZ,L2

	POP DE
	INC DE
	INC DE
	POP HL
	INC HL
	DEC IXL
	JR NZ,L1

	POP HL
	POP DE

	LD A,E
	CP low TCOLD_1
	JR NZ,CORR_1
	LD A,#FD
	LD (NT_+#2E),A

CORR_1	LD A,(DE)
	AND A
	JR Z,TC_EXIT
	RRA
	PUSH AF
	ADD A,A
	LD C,A
	ADD HL,BC
	POP AF
	JR NC,CORR_2
	DEC (HL)
	DEC (HL)
CORR_2	INC (HL)
	AND A
	SBC HL,BC
	INC DE
	JR CORR_1

TC_EXIT

	POP AF

;VolTableCreator (c) Ivan Roshin
;A - VersionForVolumeTable (0..4 - 3.xx..3.4x;
			   ;5.. - 2.x,3.5x..3.6x..VTII1.0)

	CP 5
	LD HL,#11
	LD D,H
	LD E,H
	LD A,#17
	JR NC,M1
	DEC L
	LD E,L
	XOR A
M1      LD (M2),A

	LD IX,VT_+16

	LD C,#F
INITV2  PUSH HL

	ADD HL,DE
	EX DE,HL
	SBC HL,HL

	LD B,#10
INITV1  LD A,L
M2      DB #7D
	LD A,H
	ADC A,0
	LD (IX),A
	INC IX
	ADD HL,DE
	DJNZ INITV1

	POP HL
	LD A,E
	CP #77
	JR NZ,M3
	INC E
M3      DEC C
	JR NZ,INITV2
	RET
	;JP ROUT

SETMDAD	LD (MODADDR),HL
	LD (MDADDR1),HL
	LD (MDADDR2),HL
	RET

;PTDECOD JP #C3C3

;PT2 pattern decoder
PD2_SAM	CALL SETSAM
	JP PD2_LOOP

PD2_EOff LD (IX-12+CHP.Env_En),A
	JP PD2_LOOP

PD2_ENV	LD (IX-12+CHP.Env_En),16
	LD (AYREGS+EnvTp),A
	LD A,(BC)
	INC BC
	LD L,A
	LD A,(BC)
	INC BC
	LD H,A
	LD (EnvBase),HL
	JP PD2_LOOP

PD2_ORN	CALL SETORN
	JP PD2_LOOP

PD2_SKIP INC A
	LD (IX-12+CHP.NNtSkp),A
	JP PD2_LOOP

PD2_VOL	RRCA
	RRCA
	RRCA
	RRCA
	LD (IX-12+CHP.Volume),A
	JP PD2_LOOP

PD2_DEL	CALL C_DELAY
	JP PD2_LOOP

PD2_GLIS SET 2,(IX-12+CHP.Flags)
	INC A
	LD (IX-12+CHP.TnSlDl),A
	LD (IX-12+CHP.TSlCnt),A
	LD A,(BC)
	INC BC
        LD (IX-12+CHP.TSlStp),A
	ADD A,A
	SBC A,A
        LD (IX-12+CHP.TSlStp+1),A
	SCF
	JP PD2_LP2

PT2PD	AND A

PD2_LP2	EX AF,AF'

PD2_LOOP LD A,(BC)
	INC BC
	ADD A,#20
	JR Z,PD2_REL
	JR C,PD2_SAM
	ADD A,96
	JR C,PD2_NOTE
	INC A
	JR Z,PD2_EOff
	ADD A,15
	JP Z,PD_FIN
	JR C,PD2_ENV
	ADD A,#10
	JR C,PD2_ORN
	ADD A,#40
	JR C,PD2_SKIP
	ADD A,#10
	JR C,PD2_VOL
	INC A
	JR Z,PD2_DEL
	INC A
	JR Z,PD2_GLIS
	INC A
	JR Z,PD2_PORT
	INC A
	JR Z,PD2_STOP
	LD A,(BC)
	INC BC
	LD (IX-12+CHP.CrNsSl),A
	JP PD2_LOOP

PD2_PORT RES 2,(IX-12+CHP.Flags)
	LD A,(BC)
	INC BC
	INC BC ;ignoring precalc delta to right sound
	INC BC
	SCF
	JP PD2_LP2

PD2_STOP LD (IX-12+CHP.TSlCnt),A
	JP PD2_LOOP

PD2_REL	LD (IX-12+CHP.Flags),A
	JP PD2_EXIT

PD2_NOTE LD L,A
	LD A,(IX-12+CHP.Note)
	LD (PrNote+1),A
	LD (IX-12+CHP.Note),L
	XOR A
	LD (IX-12+CHP.TSlCnt),A
	SET 0,(IX-12+CHP.Flags)
	EX AF,AF'
	JR NC,NOGLIS2
	BIT 2,(IX-12+CHP.Flags)
	JR NZ,NOPORT2
	LD (LoStep),A
	ADD A,A
	SBC A,A
	EX AF,AF'
	LD H,A
	LD L,A
	INC A
	CALL SETPORT
NOPORT2	LD (IX-12+CHP.TSlCnt),1
NOGLIS2	XOR A

PD2_EXIT LD (IX-12+CHP.PsInSm),A
	LD (IX-12+CHP.PsInOr),A
	LD (IX-12+CHP.CrTnSl),A
	LD (IX-12+CHP.CrTnSl+1),A
	JP PD_FIN

;PT3 pattern decoder
PD_OrSm	LD (IX-12+CHP.Env_En),0
	CALL SETORN
PD_SAM_	LD A,(BC)
	INC BC
	RRCA

PD_SAM	CALL SETSAM
	JP PD_LOOP

PD_VOL	RRCA
	RRCA
	RRCA
	RRCA
	LD (IX-12+CHP.Volume),A
	JP PD_LP2
	
PD_EOff	LD (IX-12+CHP.Env_En),A
	LD (IX-12+CHP.PsInOr),A
	JP PD_LP2

PD_SorE	DEC A
	JR NZ,PD_ENV
	LD A,(BC)
	INC BC
	LD (IX-12+CHP.NNtSkp),A
	JP PD_LP2

PD_ENV	CALL SETENV
	JP PD_LP2

PD_ORN	CALL SETORN
	JP PD_LOOP

PD_ESAM	LD (IX-12+CHP.Env_En),A
	LD (IX-12+CHP.PsInOr),A
	CALL NZ,SETENV
	JP PD_SAM_

PT3PD	LD A,(IX-12+CHP.Note)
	LD (PrNote+1),A
	LD L,(IX-12+CHP.CrTnSl)
	LD H,(IX-12+CHP.CrTnSl+1)
	LD (PrSlide+1),HL

PD_LOOP	LD DE,#2010
PD_LP2	LD A,(BC)
	INC BC
	ADD A,E
	JR C,PD_OrSm
	ADD A,D
	JR Z,PD_FIN
	JR C,PD_SAM
	ADD A,E
	JR Z,PD_REL
	JR C,PD_VOL
	ADD A,E
	JR Z,PD_EOff
	JR C,PD_SorE
	ADD A,96
	JR C,PD_NOTE
	ADD A,E
	JR C,PD_ORN
	ADD A,D
	JR C,PD_NOIS
	ADD A,E
	JR C,PD_ESAM
	ADD A,A
	LD E,A
	LD HL,#FFFF & (SPCCOMS+#FF20-#2000)
	ADD HL,DE
	LD E,(HL)
	INC HL
	LD D,(HL)
	PUSH DE
	JP PD_LOOP

PD_NOIS	LD (Ns_Base),A
	JP PD_LP2

PD_REL	RES 0,(IX-12+CHP.Flags)
	JP PD_RES

PD_NOTE	LD (IX-12+CHP.Note),A
	SET 0,(IX-12+CHP.Flags)
	XOR A

PD_RES	LD (PDSP_+1),SP
	LD SP,IX
	LD H,A
	LD L,A
	PUSH HL
	PUSH HL
	PUSH HL
	PUSH HL
	PUSH HL
	PUSH HL
PDSP_	LD SP,#3131

PD_FIN	LD A,(IX-12+CHP.NNtSkp)
	LD (IX-12+CHP.NtSkCn),A
	RET

C_PORTM LD A,(BC)
	INC BC
;SKIP PRECALCULATED TONE DELTA (BECAUSE
;CANNOT BE RIGHT AFTER PT3 COMPILATION)
	INC BC
	INC BC
	EX AF,AF'
	LD A,(BC) ;SIGNED TONE STEP
	INC BC
	LD (LoStep),A
	LD A,(BC)
	INC BC
	AND A
	EX AF,AF'
	LD L,(IX-12+CHP.CrTnSl)
	LD H,(IX-12+CHP.CrTnSl+1)

;Set portamento variables
;A - Delay; A' - Hi(Step); ZF' - (A'=0); HL - CrTnSl

SETPORT	RES 2,(IX-12+CHP.Flags)
	LD (IX-12+CHP.TnSlDl),A
	LD (IX-12+CHP.TSlCnt),A
	PUSH HL
	LD A,(IX-12+CHP.Note)
	LD (IX-12+CHP.SlToNt),A
	ADD A,A
	LD L,A
	LD H,high NT_
	;LD DE,NT_
	;ADD A,A
	;LD L,A
	;LD H,0
	;ADD HL,DE
	LD E,(HL)
	INC HL
	LD D,(HL)
	PUSH DE
	;LD A,(HL)
	;INC HL
	;LD H,(HL)
	;LD L,A
	;PUSH HL
PrNote	LD A,#3E
	LD (IX-12+CHP.Note),A
	ADD A,A
	LD L,A
	LD H,high NT_
	;LD H,0
	;ADD HL,DE
	LD E,(HL)
	INC HL
	LD D,(HL)
	POP HL
	SBC HL,DE
	LD (IX-12+CHP.TnDelt),L
	LD (IX-12+CHP.TnDelt+1),H
	POP DE
Version EQU $+1
	LD A,#3E
	CP 6
	JR C,OLDPRTM ;Old 3xxx for PT v3.5-
PrSlide	LD DE,#1111
	LD (IX-12+CHP.CrTnSl),E
	LD (IX-12+CHP.CrTnSl+1),D
LoStep	EQU $+1
OLDPRTM	LD A,#3E
	EX AF,AF'
	JR Z,NOSIG
	EX DE,HL
NOSIG	SBC HL,DE
	JP P,SET_STP
	CPL
	EX AF,AF'
	NEG
	EX AF,AF'
SET_STP	LD (IX-12+CHP.TSlStp+1),A
	EX AF,AF'
	LD (IX-12+CHP.TSlStp),A
	LD (IX-12+CHP.COnOff),0
	RET

C_GLISS	SET 2,(IX-12+CHP.Flags)
	LD A,(BC)
	INC BC
	LD (IX-12+CHP.TnSlDl),A
	AND A
	JR NZ,GL36
	LD A,(Version) ;AlCo PT3.7+
	CP 7
	SBC A,A
	INC A
GL36	LD (IX-12+CHP.TSlCnt),A
	LD A,(BC)
	INC BC
	EX AF,AF'
	LD A,(BC)
	INC BC
	JP SET_STP

C_SMPOS	LD A,(BC)
	INC BC
	LD (IX-12+CHP.PsInSm),A
	RET

C_ORPOS	LD A,(BC)
	INC BC
	LD (IX-12+CHP.PsInOr),A
	RET

C_VIBRT	LD A,(BC)
	INC BC
	LD (IX-12+CHP.OnOffD),A
	LD (IX-12+CHP.COnOff),A
	LD A,(BC)
	INC BC
	LD (IX-12+CHP.OffOnD),A
	XOR A
	LD (IX-12+CHP.TSlCnt),A
	LD (IX-12+CHP.CrTnSl),A
	LD (IX-12+CHP.CrTnSl+1),A
	RET

C_ENGLS	LD A,(BC)
	INC BC
	LD (Env_Del),A
	LD (CurEDel),A
	LD A,(BC)
	INC BC
	LD L,A
	LD A,(BC)
	INC BC
	LD H,A
	LD (ESldAdd),HL
	RET

C_DELAY	LD A,(BC)
	INC BC
	LD (Delay),A
	RET
	
SETENV	LD (IX-12+CHP.Env_En),E
	LD (AYREGS+EnvTp),A
	LD A,(BC)
	INC BC
	LD H,A
	LD A,(BC)
	INC BC
	LD L,A
	LD (EnvBase),HL
	XOR A
	LD (IX-12+CHP.PsInOr),A
	LD (CurEDel),A
	;LD H,A
	;LD L,A
	;LD (CurESld),HL
C_NOP	RET

SETORN	ADD A,A
	LD E,A
	LD D,0
	LD (IX-12+CHP.PsInOr),D
OrnPtrs EQU $+1
	LD HL,#2121
	ADD HL,DE
	LD E,(HL)
	INC HL
	LD D,(HL)
MDADDR2 EQU $+1
	LD HL,#2121
	ADD HL,DE
	LD (IX-12+CHP.OrnPtr),L
	LD (IX-12+CHP.OrnPtr+1),H
	RET

SETSAM	ADD A,A
	LD E,A
	LD D,0
SamPtrs EQU $+1
	LD HL,#2121
	ADD HL,DE
	LD E,(HL)
	INC HL
	LD D,(HL)
MDADDR1	EQU $+1
	LD HL,#2121
	ADD HL,DE
	LD (IX-12+CHP.SamPtr),L
	LD (IX-12+CHP.SamPtr+1),H
	RET

;ALL 16 ADDRESSES TO PROTECT FROM BROKEN PT3 MODULES
SPCCOMS DW C_NOP
	DW C_GLISS
	DW C_PORTM
	DW C_SMPOS
	DW C_ORPOS
	DW C_VIBRT
	DW C_NOP
	DW C_NOP
	DW C_ENGLS
	DW C_DELAY
	DW C_NOP
	DW C_NOP
	DW C_NOP
	DW C_NOP
	DW C_NOP
	DW C_NOP

CHREGS	LD (CSP_+1),SP
	LD SP,HL
	POP DE
	LD H,0
	LD A,(IX+CHP.PsInOr)
	LD L,A
	ADD HL,SP
	INC A
		;PT2	PT3
OrnCP	INC A	;CP E	CP D
	JR C,CH_ORPS
OrnLD	DB 1	;LD A,D	LD A,E
CH_ORPS	LD (IX+CHP.PsInOr),A
	LD A,(IX+CHP.Note)
	ADD A,(HL)
	JP P,CH_NTP
	XOR A
CH_NTP	CP 96
	JR C,CH_NOK
	LD A,95
CH_NOK	ADD A,A
	EX AF,AF'
	LD L,(IX+CHP.SamPtr)
	LD H,(IX+CHP.SamPtr+1)
	LD SP,HL
	POP DE
	LD H,0
	LD A,(IX+CHP.PsInSm)
	LD B,A
	ADD A,A
SamClc2	ADD A,A ;or ADD A,B for PT2
	LD L,A
	ADD HL,SP
	LD SP,HL
	LD A,B
	INC A
		;PT2	PT3
SamCP	INC A	;CP E	CP D
	JR C,CH_SMPS
SamLD	DB 1	;LD A,D	LD A,E
CH_SMPS	LD (IX+CHP.PsInSm),A
	POP BC
	POP HL

;Convert PT2 sample to PT3
		;PT2		PT3
SamCnv	POP HL  ;BIT 2,C	JR e_
	POP HL	
	LD H,B
	JR NZ,$+8
	EX DE,HL
	AND A
	SBC HL,HL
	SBC HL,DE
	LD D,C
	RR C
	SBC A,A
	CPL
	AND #3E
	RR C
	RR B
	AND C
	LD C,A
	LD A,B
	RRA
	RRA
	RR D
	RRA
	AND #9F
	LD B,A

e_	LD E,(IX+CHP.TnAcc)
	LD D,(IX+CHP.TnAcc+1)
	ADD HL,DE
	BIT 6,B
	JR Z,CH_NOAC
	LD (IX+CHP.TnAcc),L
	LD (IX+CHP.TnAcc+1),H
CH_NOAC EX DE,HL
	EX AF,AF'
	;ADD A, low NT_
	LD L,A
	;ADC A,NT_/256
	;SUB L
	;LD H,A
	ld H,high NT_
	LD SP,HL
	POP HL
	ADD HL,DE
	LD E,(IX+CHP.CrTnSl)
	LD D,(IX+CHP.CrTnSl+1)
	ADD HL,DE
CSP_	LD SP,#3131
	PUSH HL;EX (SP),HL
	XOR A
	OR (IX+CHP.TSlCnt)
	JR Z,CH_AMP
	DEC (IX+CHP.TSlCnt)
	JR NZ,CH_AMP
	LD A,(IX+CHP.TnSlDl)
	LD (IX+CHP.TSlCnt),A
	LD L,(IX+CHP.TSlStp)
	LD H,(IX+CHP.TSlStp+1)
	LD A,H
	ADD HL,DE
	LD (IX+CHP.CrTnSl),L
	LD (IX+CHP.CrTnSl+1),H
	BIT 2,(IX+CHP.Flags)
	JR NZ,CH_AMP
	LD E,(IX+CHP.TnDelt)
	LD D,(IX+CHP.TnDelt+1)
	AND A
	JR Z,CH_STPP
	EX DE,HL
CH_STPP SBC HL,DE
	JP M,CH_AMP
	LD A,(IX+CHP.SlToNt)
	LD (IX+CHP.Note),A
	XOR A
	LD (IX+CHP.TSlCnt),A
	LD (IX+CHP.CrTnSl),A
	LD (IX+CHP.CrTnSl+1),A
CH_AMP	LD A,(IX+CHP.CrAmSl)
	BIT 7,C
	JR Z,CH_NOAM
	BIT 6,C
	JR Z,CH_AMIN
	CP 15
	JR Z,CH_NOAM
	INC A
	JR CH_SVAM
CH_AMIN	CP -15
	JR Z,CH_NOAM
	DEC A
CH_SVAM	LD (IX+CHP.CrAmSl),A
CH_NOAM	LD L,A
	LD A,B
	AND 15
	ADD A,L
	JP P,CH_APOS
	XOR A
CH_APOS	CP 16
	JR C,CH_VOL
	LD A,15
CH_VOL	OR (IX+CHP.Volume)
	;ADD A,low VT_
	LD L,A
	;ADC A,VT_/256
	;SUB L
	;LD H,A
	ld H,high VT_
	LD A,(HL)
CH_ENV	BIT 0,C
	JR NZ,CH_NOEN
	OR (IX+CHP.Env_En)
CH_NOEN	;LD (Ampl),A
	EX AF,AF'
	BIT 7,B
	LD A,C
	JR Z,NO_ENSL
	RLA
	RLA
	SRA A
	SRA A
	SRA A
	ADD A,(IX+CHP.CrEnSl) ;SEE COMMENT BELOW
	BIT 5,B
	JR Z,NO_ENAC
	LD (IX+CHP.CrEnSl),A
NO_ENAC	;LD HL,AddToEn
	;ADD A,(HL) ;BUG IN PT3 - NEED WORD HERE
	;LD (HL),A
	ADD IYL
	LD IYL,A
	JP CH_MIX
NO_ENSL RRA
	ADD A,(IX+CHP.CrNsSl)
	LD (AddToNs),A
	BIT 5,B
	JR Z,CH_MIX
	LD (IX+CHP.CrNsSl),A
CH_MIX	LD A,B
	RRA
	AND #48
	OR IYH
	RRCA
	LD IYH,A
	;LD HL,AYREGS+Mixer
	;OR (HL)
	;RRCA
	;LD (HL),A
	POP HL
	XOR A
	OR (IX+CHP.COnOff)
	RET Z
	DEC (IX+CHP.COnOff)
	RET NZ
	XOR (IX+CHP.Flags)
	LD (IX+CHP.Flags),A
	RRA
	LD A,(IX+CHP.OnOffD)
	JR C,CH_ONDL
	LD A,(IX+CHP.OffOnD)
CH_ONDL	LD (IX+CHP.COnOff),A
	RET

;==========================================================
;
;

PLAY	;DEC A
	;LD (AYREGS+EnvTp),A
	LD HL,DelyCnt
	DEC (HL)
	JP NZ,PL2

	LD HL,ChanA+CHP.NtSkCn
	DEC (HL)
	JR NZ,PL1B
AdInPtA EQU $+1
	LD BC,#0101
	LD A,(BC)
	AND A
	JR NZ,PL1A
	LD D,A
	LD (Ns_Base),A
CrPsPtr EQU $+1
	LD HL,#2121
	INC HL
	LD A,(HL)
	CP #FF
	JR NZ,PLNLP

	IF LoopChecker
	CALL CHECKLP
	ENDIF

LPosPtr EQU $+1
	LD HL,#2121
	LD A,(HL)
PLNLP	LD (CrPsPtr),HL
		;PT2		PT3
PsCalc	DEC A	;ADD A,A	NOP
	DEC A	;ADD A,(HL)	NOP
	ADD A,A
	LD E,A
	RL D

	IF CurPosCounter
	LD A,L
PosSub	SUB #D6
	LD (CurPos),A
	ENDIF

PatsPtr EQU $+1
	LD HL,#2121
	ADD HL,DE
MODADDR	EQU $+1
	LD DE,#1111
	LD (PSP_+1),SP
	LD SP,HL
	POP HL
	ADD HL,DE
	LD B,H
	LD C,L
	POP HL
	ADD HL,DE
	LD (AdInPtB),HL
	POP HL
	ADD HL,DE
	LD (AdInPtC),HL
PSP_	LD SP,#3131

PL1A	LD IX,ChanA+12
PDCALL1	CALL 0
	LD (AdInPtA),BC

PL1B	LD HL,ChanB+CHP.NtSkCn
	DEC (HL)
	JR NZ,PL1C
	LD IX,ChanB+12
AdInPtB	EQU $+1
	LD BC,#0101
PDCALL2	CALL 0
	LD (AdInPtB),BC

PL1C	LD HL,ChanC+CHP.NtSkCn
	DEC (HL)
	JR NZ,PL1D
	LD IX,ChanC+12
AdInPtC	EQU $+1
	LD BC,#0101
PDCALL3	CALL 0
	LD (AdInPtC),BC

Delay	EQU $+1
PL1D	LD A,#3E
	LD (DelyCnt),A

PL2	;XOR A
	;LD (AddToEn),A
	;LD (AYREGS+Mixer),A
	LD IY,0 ; IYH is Mixer, IYL is AddToEn

	LD A,(ChanA+CHP.Flags)
	RRCA
	JP C,FullChanA

		;LD HL,AYREGS+Mixer
		;RRC (HL)
		XOR A
		LD (AYREGS+AmplA),A
		LD HL,ChanA+CHP.COnOff
		OR (HL)
		JP Z,DoneChanA
		DEC (HL)
		JR NZ,DoneChanA

		EX DE,HL
		LD HL,ChanA+CHP.Flags
		XOR (HL)
		LD (HL),A
		LD A,(ChanA+CHP.OnOffD)
		JP PO,ONDL_A
		LD A,(ChanA+CHP.OffOnD)
ONDL_A		LD (DE),A
		JP DoneChanA
FullChanA:
	LD IX,ChanA
	LD HL,(ChanA+CHP.OrnPtr)
	CALL CHREGS
	LD (AYREGS+TonA),HL
	EX AF,AF'
	LD (AYREGS+AmplA),A
DoneChanA:

	LD A,(ChanB+CHP.Flags)
	RRCA
	JP C,FullChanB

		LD A,IYH
		RRCA
		LD IYH,A
		;LD HL,AYREGS+Mixer
		;RRC (HL)
		XOR A
		LD (AYREGS+AmplB),A
		LD HL,ChanB+CHP.COnOff
		OR (HL)
		JP Z,DoneChanB
		DEC (HL)
		JR NZ,DoneChanB

		EX DE,HL
		LD HL,ChanB+CHP.Flags
		XOR (HL)
		LD (HL),A
		LD A,(ChanB+CHP.OnOffD)
		JP PO,ONDL_B
		LD A,(ChanB+CHP.OffOnD)
ONDL_B		LD (DE),A
		JP DoneChanB
FullChanB:
	LD IX,ChanB
	LD HL,(ChanB+CHP.OrnPtr)
	CALL CHREGS
	LD (AYREGS+TonB),HL
	EX AF,AF'
	LD (AYREGS+AmplB),A
DoneChanB:

	LD A,(ChanC+CHP.Flags)
	RRCA
	JP C,FullChanC

		LD A,IYH
		RRCA
		LD IYH,A
		;LD HL,AYREGS+Mixer
		;RRC (HL)
		XOR A
		LD (AYREGS+AmplC),A
		LD HL,ChanC+CHP.COnOff
		OR (HL)
		JP Z,DoneChanC
		DEC (HL)
		JR NZ,DoneChanC

		EX DE,HL
		LD HL,ChanC+CHP.Flags
		XOR (HL)
		LD (HL),A
		LD A,(ChanC+CHP.OnOffD)
		JP PO,ONDL_C
		LD A,(ChanC+CHP.OffOnD)
ONDL_C		LD (DE),A
		JP DoneChanC
FullChanC:
	LD IX,ChanC
	LD HL,(ChanC+CHP.OrnPtr)
	CALL CHREGS
	LD (AYREGS+TonC),HL
	EX AF,AF'
	LD (AYREGS+AmplC),A
DoneChanC:

	LD A,IYH
	LD (AYREGS+Mixer),a

	LD A,0
Ns_Base	EQU $-1
	ADD 0
AddToNs	EQU $-1
	LD (AYREGS+Noise),A

;AddToEn EQU $+1
;	LD A,#3E
	LD A,IYL
	LD L,A
	ADD A,A
	SBC A,A
	LD H,A

EnvBase EQU $+1
	LD DE,0
	ADD HL,DE
	LD (AYREGS+Env),HL

CurEDel	EQU $+1
	LD A,0
	OR A
	JR Z,ROUT
	;RET Z
	DEC A
	JR NZ,SaveEDel

	;LD HL,(EnvBase)
ESldAdd	EQU $+1
	LD HL,#2121
	ADD HL,DE
	;LD (CurESld),HL
	LD (EnvBase),HL

Env_Del	EQU $+1
	LD A,#3E

SaveEDel LD (CurEDel),a
	;RET


ROUT	ld de,#ffbf
	ld c,#fd
	ld hl,AYREGS
	DUP 13
	ld b,d
	out (c),l
	ld b,e
	outi
	EDUP
	ld b,d
	out (c),l
	ld a,(hl)
	and a
	ret m
	ld b,e
	out (c),a
	ld (hl),d
	ret

NT_DATA	DB (T_NEW_0-T1_)*2
	DB TCNEW_0-T_
	DB (T_OLD_0-T1_)*2+1
	DB TCOLD_0-T_
	DB (T_NEW_1-T1_)*2+1
	DB TCNEW_1-T_
	DB (T_OLD_1-T1_)*2+1
	DB TCOLD_1-T_
	DB (T_NEW_2-T1_)*2
	DB TCNEW_2-T_
	DB (T_OLD_2-T1_)*2
	DB TCOLD_2-T_
	DB (T_NEW_3-T1_)*2
	DB TCNEW_3-T_
	DB (T_OLD_3-T1_)*2
	DB TCOLD_3-T_

T_

TCOLD_0	DB #00+1,#04+1,#08+1,#0A+1,#0C+1,#0E+1,#12+1,#14+1
	DB #18+1,#24+1,#3C+1,0
TCOLD_1	DB #5C+1,0
TCOLD_2	DB #30+1,#36+1,#4C+1,#52+1,#5E+1,#70+1,#82,#8C,#9C
	DB #9E,#A0,#A6,#A8,#AA,#AC,#AE,#AE,0
TCNEW_3	DB #56+1
TCOLD_3	DB #1E+1,#22+1,#24+1,#28+1,#2C+1,#2E+1,#32+1,#BE+1,0
TCNEW_0	DB #1C+1,#20+1,#22+1,#26+1,#2A+1,#2C+1,#30+1,#54+1
	DB #BC+1,#BE+1,0
TCNEW_1 EQU TCOLD_1
TCNEW_2	DB #1A+1,#20+1,#24+1,#28+1,#2A+1,#3A+1,#4C+1,#5E+1
	DB #BA+1,#BC+1,#BE+1,0

PT3EMPTYORN EQU $-1
	DB 1,0

;first 12 values of tone tables (packed)

T_PACK	DB high (#06EC*2), low (#06EC*2)
	DB #0755-#06EC
	DB #07C5-#0755
	DB #083B-#07C5
	DB #08B8-#083B
	DB #093D-#08B8
	DB #09CA-#093D
	DB #0A5F-#09CA
	DB #0AFC-#0A5F
	DB #0BA4-#0AFC
	DB #0C55-#0BA4
	DB #0D10-#0C55
	DB high (#066D*2), low (#066D*2)
	DB #06CF-#066D
	DB #0737-#06CF
	DB #07A4-#0737
	DB #0819-#07A4
	DB #0894-#0819
	DB #0917-#0894
	DB #09A1-#0917
	DB #0A33-#09A1
	DB #0ACF-#0A33
	DB #0B73-#0ACF
	DB #0C22-#0B73
	DB #0CDA-#0C22
	DB high (#0704*2), low (#0704*2)
	DB #076E-#0704
	DB #07E0-#076E
	DB #0858-#07E0
	DB #08D6-#0858
	DB #095C-#08D6
	DB #09EC-#095C
	DB #0A82-#09EC
	DB #0B22-#0A82
	DB #0BCC-#0B22
	DB #0C80-#0BCC
	DB #0D3E-#0C80
	DB high (#07E0*2), low (#07E0*2)
	DB #0858-#07E0
	DB #08E0-#0858
	DB #0960-#08E0
	DB #09F0-#0960
	DB #0A88-#09F0
	DB #0B28-#0A88
	DB #0BD8-#0B28
	DB #0C80-#0BD8
	DB #0D60-#0C80
	DB #0E10-#0D60
	DB #0EF8-#0E10

;vars from here can be stripped
;you can NOT move VARS to any other address

	ALIGN #100

VARS

AYREGS

VT_	DS 256 ;CreatedVolumeTableAddress

;EnvBase	EQU VT_+14

T1_	EQU VT_+16 ;Tone tables data depacked here

T_OLD_1	EQU T1_
T_OLD_2	EQU T_OLD_1+24
T_OLD_3	EQU T_OLD_2+24
T_OLD_0	EQU T_OLD_3+2
T_NEW_0	EQU T_OLD_0
T_NEW_1	EQU T_OLD_1
T_NEW_2	EQU T_NEW_0+24
T_NEW_3	EQU T_OLD_3

PT2EMPTYORN EQU VT_+31 ;1,0,0 sequence

Ampl	EQU AYREGS+AmplC

VAR0END	EQU VT_+16 ;INIT zeroes from VARS to VAR0END-1

	;ALIGN #100

NT_	DS 192 ;CreatedNoteTableAddress NB: ALIGNED ITSELF

;ChannelsVars
	STRUCT	CHP
;reset group
PsInOr	DB 0
PsInSm	DB 0
CrAmSl	DB 0
CrNsSl	DB 0
CrEnSl	DB 0
TSlCnt	DB 0
CrTnSl	DW 0
TnAcc	DW 0
COnOff	DB 0
;reset group

OnOffD	DB 0

;IX for PTDECOD here (+12)
OffOnD	DB 0
OrnPtr	DW 0
SamPtr	DW 0
NNtSkp	DB 0
Note	DB 0
SlToNt	DB 0
Env_En	DB 0
Flags	DB 0
 ;Enabled - 0,SimpleGliss - 2
TnSlDl	DB 0
TSlStp	DW 0
TnDelt	DW 0
NtSkCn	DB 0
Volume	DB 0
	ENDS

ChanA	DS CHP
ChanB	DS CHP
ChanC	DS CHP
	DISPLAY "ChanRec: ", /D, $-ChanC

;GlobalVars
DelyCnt	DB 0

VARSEND EQU $

MDLADDR EQU $

;Release 0 steps:
;02/27/2005
;Merging PT2 and PT3 players; debug
;02/28/2005
;debug; optimization
;03/01/2005
;Migration to SjASM; conditional assembly (ZX, MSX and
;visualization)
;03/03/2005
;SETPORT subprogram (35 bytes shorter)
;03/05/2005
;fixed CurPosCounter error
;03/06/2005
;Added ACB and BAC channels swapper (for Spectre); more cond.
;assembly keys; optimization
;Release 1 steps:
;04/15/2005
;Removed loop bit resetting for no loop build (5 bytes shorter)
;04/30/2007
;New 1.xx and 2.xx interpretation for PT 3.7+.

;Tests in IMMATION TESTER V1.0 by Andy Man/POS
;(for minimal build)
;Module name/author	Min tacts	Max tacts
;PT3 (a little slower than standalone player)
;Spleen/Nik-O		1720		9368
;Chuta/Miguel		1720		9656
;Zhara/Macros		4536		8792
;PT2 (more slower than standalone player)
;Epilogue/Nik-O		3928		10232
;NY tHEMEs/zHenYa	3848		9208
;GUEST 4/Alex Job	2824		9352
;KickDB/Fatal Snipe	1720		9880

;Size (minimal build for ZX Spectrum):
;Code block #7B9 bytes
;Variables #21D bytes (can be stripped)
;Size in RAM #7B9+#21D=#9D6 (2518) bytes

;Notes:
;Pro Tracker 3.4r can not be detected by header, so PT3.4r tone
;tables realy used only for modules of 3.3 and older versions.
