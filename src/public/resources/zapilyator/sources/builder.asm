	; build
	if (_ERRORS == 0 && _WARNINGS == 0)
	;LABELSLIST "user.l"
	savesna SNA_FILENAME, page0s

	page 0
	org #4000
	
	; trd monoloader
 	MODULE boot
Basic:
	db #00,#01	;номер строки
	dw EndLine1 - Line1
Line1:
	db #EA	;REM
	ld sp, #5FFE
	di

	ifdef _page7
	ld a,#17, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page7s
	if low (page7e - page7s) == 0
	ld bc, (page7e - page7s)/256*256 + 5
	else
	ld bc, (page7e - page7s + 256)/256*256 + 5
	endif
	call #3d13
	endif
	
	ifdef _page6
	ld a,#16, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page6s
	if low (page6e - page6s) == 0
	ld bc, (page6e - page6s)/256*256 + 5
	else
	ld bc, (page6e - page6s + 256)/256*256 + 5
	endif
	call #3d13
	endif
	
	ifdef _page4
	ld a,#14, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page4s
	if low (page4e - page4s) == 0
	ld bc, (page4e - page4s)/256*256 + 5
	else
	ld bc, (page4e - page4s + 256)/256*256 + 5
	endif
	call #3d13
	endif
	
	ifdef _page3
	ld a,#13, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page3s
	if low (page3e - page3s) == 0
	ld bc, (page3e - page3s)/256*256 + 5
	else
	ld bc, (page3e - page3s + 256)/256*256 + 5
	endif
	call #3d13
	endif
	
	ifdef _page1
	ld a,#11, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page1s
	if low (page1e - page1s) == 0
	ld bc, (page1e - page1s)/256*256 + 5
	else
	ld bc, (page1e - page1s + 256)/256*256 + 5
	endif
	call #3d13
	endif

	ld a,#10, bc,#7ffd : out (c),a
	ld de, (#5cf4)
	ld hl, page0s
	push hl
	if low (page0e - page0s) == 0
	ld bc, (page0e - page0s)/256*256 + 5
	else
	ld bc, (page0e - page0s + 256)/256*256 + 5
	endif
	jp #3d13
	
	db #0D
EndLine1:	db #00,#02
	dw EndLine2 - Line2
Line2:	db #20,#FD,#B0
	db #22,#32,#34,#35,#37,#35,#22;clear val "24575"
	db #3A; :
	db #F9,#C0,#28,#35;randomize usr (5+256*peek val "23635"+peek val "23636"
	db #0E,#00,#00,#05,#00,#00,#2B
	db #32,#35,#36
	db #0E,#00,#00,#00,#01,#00,#2A,#BE

	db #B0
	db #22,#32,#33,#36,#33,#36,#22;"23635"
	db #2B;???
	db #BE
	db #B0
	db #22,#32,#33,#36,#33,#35,#22;"23636"
	db #29,#0D;)
	db #80
	db #AA,1,0;;;;;;;;;;;;;autorun line,change program length to -4, e.g. 83-4=79
EndLine2:
EndBasic:
	ENDMODULE

	emptytrd TRD_FILENAME
	savetrd TRD_FILENAME, "boot.B", boot.Basic, boot.EndBasic - boot.Basic

	ifdef _page7
    page 7
    savetrd TRD_FILENAME, "7.C",page7s, page7e-page7s
    savebin "page7.c", page7s, page7e-page7s
	endif

	ifdef _page6
    page 6
    savetrd TRD_FILENAME, "6.C",page6s, page6e-page6s
    savebin "page6.c", page6s, page6e-page6s
	endif
	
	ifdef _page4
    page 4
    savetrd TRD_FILENAME, "4.C",page4s, page4e-page4s
    savebin "page4.c", page4s, page4e-page4s
	endif
	
	ifdef _page3
    page 3
    savetrd TRD_FILENAME, "3.C",page3s, page3e-page3s
    savebin "page3.c", page3s, page3e-page3s
	endif
	
	ifdef _page1
    page 1
    savetrd TRD_FILENAME, "1.C",page1s, page1e-page1s
    savebin "page1.c", page1s, page1e-page1s
	endif

	page 0
    savetrd TRD_FILENAME, "0.C",page0s, page0e-page0s
    savebin "page0.c", page0s, page0e-page0s
	
	endif